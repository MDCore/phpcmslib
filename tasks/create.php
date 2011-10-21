<?
/*
 * TODO
 * - Add face to $allowed_faces in conf/app
 * - NBNB controllers/views etc
 * - allow overriding defaults like extends
 */
class tasks_create implements lib_task
{
  private $valid_objects = array('face', 'controller', 'model', 'migration');
  private $valid_migration_field_types = array('string', 'integer', 'text', 'date', 'active', 'timestamps');
  private $valid_model_attributes = array('validates_presence_of', 'has_one', 'has_many', 'belongs_to', 'changelog', 'acts_as_nested_set', 'display_field');

  public function run($arguments) {
    $object = $arguments[2]; if (is_null($object) || $object == '') { echo 'No object specified (e.g. face)'; die(); }

    switch ($object) {
    case 'face':
      $face_name = $arguments[3]; if (is_null($face_name) || $face_name == '') { echo 'No face name specified'; die(); }
      $this->create_face($face_name);
      break;
    case 'controller':
      $face_name = $arguments[3]; if (is_null($face_name) || $face_name == '') { echo 'No face name specified'; die(); }
      $controller_name = $arguments[4]; if (is_null($controller_name) || $controller_name == '') { echo 'No controller name name specified'; die(); }
      $actions = array_slice($arguments, 5);

      $this->create_controller($face_name, $controller_name, array('actions' => $actions));
      break;
    case 'model':
      $model_name = $arguments[3]; if (is_null($model_name) || $model_name == '') { echo 'No model name specified'; die(); }
      $fields = array_slice($arguments, 4);

      $skip_migration = false;
      $spos = array_search('skip-migration', $fields);
      if ($spos !== false) {
        unset($fields[$spos]);
	$fields = array_values($fields); /* reconstitute the fields array */
        $skip_migration = true;
      }

      $skip_cm_controller = false;
      $spos = array_search('skip-cm-controller', $fields);
      if ($spos !== false) {
        unset($fields[$spos]);
	$fields = array_values($fields); /* reconstitute the fields array */
        $skip_cm_controller = true;
      }

      $this->create_model($model_name, $fields, $skip_migration, $skip_cm_controller);
      break;
    case 'migration':
      $migration_name = $arguments[3]; if (is_null($migration_name) || $migration_name == '') { echo 'No migration name specified'; die(); }
      $fields = array_slice($arguments, 4);

      $this->create_migration($migration_name, $fields);
      break;
    case 'view':
      break;
    case 'uploads_table':
        $this->uploads_table();
      break;

    default:
      echo "Error: object must be one of:\r\n";
      foreach($valid_objects as $lo) {
          echo $lo."\r\n";
      }
    }
  }

  function create_face($face_name) {
    $face_structure = array('controllers', 'views', 'layouts', 'assets/images', 'assets/scripts', 'assets/stylesheets', 'assets/swfs');
    global $path_to_root;
    if (file_exists($path_to_root.'/'.$face_name)) {
        echo "face $face_name already exists.";
        return false;
    }
    //make the face
    $result = mkdir($path_to_root.'/'.$face_name);
    if (!$result) {
      echo("unable to create face directory $face_name");
      return false;
    } else {
      echo("created $face_name\r\n");
    }

    /* if we've made it this far it means we can create directories */
    $face_root = $path_to_root.'/'.$face_name.'/';
    foreach ($face_structure as $folder) {
      mkdir($path_to_root.'/'.$face_name.'/'.$folder, 0777, true); //true is like mkdir -p
    }

    //create the face controller
    $this->create_controller($face_name, 'face', null, 'action_controller');

      //todo do this automagically
      echo "\r\nTo use this face add $face_name to \$allowed_faces in config/application.php";

    return true;
  }

  function create_controller($face_name, $controller_name, $template_fields = null, $extends = 'face_controller') {
    global $path_to_root;

    /* create the face first if needed */
    if (!file_exists($path_to_root.'/'.$face_name)) {
      $result = $this->create_face($face_name);
      if (!$result) {
        return false;
      }
    }

    if (!is_null($template_fields) && isset($template_fields['actions'])) {
      $actions = $template_fields['actions'];
      foreach ($actions as $action) {
        $actions_text .= $this->parse_template('method', $action);
      }
    } else {
      $actions_text = '';
    }

    /* create the file */
    if ($face_name == 'cm') {

      if (!is_null($template_fields) && isset($template_fields['list_fields'])) {
	$list_fields = $template_fields['list_fields'];
      }

      $this->save_file("$face_name/controllers/{$controller_name}_controller.php", $this->parse_template('cm_controller', array($controller_name, $extends, $list_fields, $actions_text)));
    } else {
      $this->save_file("$face_name/controllers/{$controller_name}_controller.php", $this->parse_template('controller', array($controller_name, $extends, $actions_text)));
    }

    /* create the controller folder in face/views */
    if ($controller_name !== 'face' && $face_name !== 'cm') {
      $filename = $face_name.'/views/'.$controller_name;
      if (file_exists("$path_to_root/$filename")) {
        $result = false;
      } else {
        $result = mkdir("$path_to_root/$filename");
      }
      if ($result) { echo 'created '; } else { echo 'exists '; }
      echo $filename."\r\n";

      /* create the view files */
      if (isset($actions)) {
        foreach ($actions as $action) {
          $this->save_file("$face_name/views/$controller_name/$action.php", "find me in $face_name/views/$controller_name/$action.php");
        }
      }
    }
    return true;
  }

  public function create_model($model_name, $fields_and_attributes, $skip_migration, $skip_cm_controller, $extends = 'AR') {
    global $path_to_root;

    $fields = $fields_and_attributes;
    $attributes = array();
    for ($i=0;$i<sizeof($fields_and_attributes);$i++) {
      if (strpos($fields[$i], ':') > 0) {
        $fields[$i] = explode(':', $fields[$i]);
        if (in_array($fields[$i][0], $this->valid_model_attributes)) {
          $attributes[] = $fields[$i];
          unset($fields[$i]);
        }
      } else {
        if (in_array($fields[$i], $this->valid_model_attributes)) {
          $attributes[] = $fields[$i];
          unset($fields[$i]);
        }
      }
    }
    /* reformat fields */
    $cm_list_fields = array();
    for ($i=0; $i < sizeof($fields); $i++) {
      if (is_array($fields[$i])) {
	$cm_list_fields[] = $fields[$i][0];
        $fields[$i] = implode(':', $fields[$i]);
      } else {
	if ($fields[$i] !== 'timestamps') {
	  $cm_list_fields[] = $fields[$i];
	}
      }
    }

    /* build the attribute text */

    $attributes_text = "";
    for ($i=0; $i < sizeof($attributes); $i++) {
      $attributes_text .= '  public $';
      if (is_array($attributes[$i])) {
        $attributes_text .= "{$attributes[$i][0]} = '{$attributes[$i][1]}';";
      } else {
        $attributes_text .= "{$attributes[$i]};";
      }
      $attributes_text .= "\r\n";
    }

    /* create the file */
    $this->save_file("models/{$model_name}.php", $this->parse_template('model', array($model_name, $extends, $attributes_text)));

    if (!$skip_migration) {
      $this->create_migration('create_'.$model_name, $fields, $model_name);
    }
    if (!$skip_cm_controller) {
      $this->create_controller('cm', pluralize($model_name), array('list_fields' => implode(',', $cm_list_fields)));
    }

    return true;
  }

  public function create_migration($migration_name, $fields, $model_name = null) {
    if (!$model_name) {
      $model_name = $migration_name;
    }

    /* generate the migration prefix */
    $migration_prefix = date('YmdHis');

    /* sort out the fields */
    $fields_text = '';
    for ($i=0;$i<sizeof($fields);$i++) {
      $is_valid_field = false;
      if (strpos($fields[$i], ':') > 0) {
        $fields[$i] = explode(':', $fields[$i]);
        if (in_array($fields[$i][1], $this->valid_migration_field_types)) {
          $is_valid_field = true;
          $fields_text .= "    array('{$fields[$i][0]}', '{$fields[$i][1]}'),\r\n";
        }
      } else {
        if (in_array($fields[$i], $this->valid_migration_field_types)) {
          $is_valid_field = true;
          $fields_text .= "    '{$fields[$i]}',\r\n";
        }
      }
      if (!$is_valid_field) {
        $unknown = $fields[$i];
        if (is_array($unknown)) {
          $unknown = $unknown[1];
        }
        die('Error: unknown field type '.$unknown);
      }
    }
    if ($fields_text) {
      $fields_text = substr($fields_text, 0, strlen($fields_text)-3);
    }

    /* create SQL migrations as blank files */
    if (substr($migration_name, -4) == '.sql') {
      $extension = '';
      $body = "";
    } else {
      $extension = '.php';
      $body = $this->parse_template('migration', array($model_name, $fields_text));
    }
    /* create the file */
    $this->save_file("db/migrations/{$migration_prefix}_{$migration_name}{$extension}", $body);
    return true;

  }

  public function uploads_table() {

    /* generate the migration prefix */
    $migration_prefix = date('YmdHis');
    $migration_name = 'uploads';

    /* create the file */
    $this->save_file("db/migrations/{$migration_prefix}_{$migration_name}.php", $this->parse_template('upload_migration', null));

    /* create the model file */
    $this->save_file("models/upload.php", $this->parse_template('upload_model', null));
    return true;
  }

  private function parse_template($template, $arguments) {
    if (!is_array($arguments)) { $arguments = array($arguments); }
    global $path_to_lib;
    $result = file_get_contents(
      "$path_to_lib/tasks/create_templates/$template.php"
    );
    if ($result) {
      $arguments = array_merge(array($result), $arguments);
      $result = call_user_func_array('sprintf', $arguments);
    }

    return $result;
  }
  private function save_file($filename, $contents) {
    global $path_to_root;
    if (file_exists("$path_to_root/$filename")) {
      echo "exists ".$filename."\r\n";
      return false;
    }
    $result = file_put_contents("$path_to_root/$filename", $contents);
    if ($result !== false) { echo 'created '; } else { echo 'failed '; }
    echo $filename."\r\n";
    return true;
  }


  public function help() {
?>
Create : Create objects
=======================
usage:
------------------------------------------------------------------------------------------------------------------------------
To create a face:
create face <name>

------------------------------------------------------------------------------------------------------------------------------
To create a controller:
create controller <face_name> <controller name> <action 1> <action 2> ... <action n>

------------------------------------------------------------------------------------------------------------------------------
To create a model:
create model <model_name> <column:datatype> <column:datatype> <property:value> [...]
you can also pass parameters to define relationships. E.g.:
create model product name:string description:text active timestamps has_many:product_parts belongs_to:category
Note that 'id' as primary key is automatically added

Field types are:
string, integer, text, active, timestamps

Additional properties are:
has_one               comma seperated value
has_many              comma seperated value
belongs_to            comma seperated value
changelog             no value
acts_as_nested_set    no value
display_field         single value for display_field
validates_presence_of comma seperated value

To skip creating the migration:
--skip-migration

To skip creating the cm controller:
--skip-cm-controller

------------------------------------------------------------------------------------------------------------------------------
To create a migration:
create migration <migration_name> <column:datatype> <column:datatype> [...]
E.g.:
create migration name:string description:text active timestamps

Field types are:
string, integer, text, active, timestamps

------------------------------------------------------------------------------------------------------------------------------
To create the uploads table:
create uploads_table

This also creates the model
------------------------------------------------------------------------------------------------------------------------------
<?
  }
}
?>
