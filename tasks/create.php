<?
/*
 * TODO
 * - Documentation
 * - Add face to $allowed_faces in conf/app
 * - NBNB controllers/views etc
 */
class tasks_create
{
  private $legitimate_objects = array('face', 'controller');

  public function run($arguments) {
    $object = $arguments[2]; if (is_null($object) || $object == '') { echo 'No object specified (e.g. face)'; die(); }

    switch ($object) {
    case 'face':
      $face_name = $arguments[3]; if (is_null($face_name) || $face_name == '') { echo 'No face name specified'; die(); }
      $this->create_face($object_name);
      echo "created face $object_name\r\n";

      //todo do this automagically
      echo "\r\nTo use this face add $object_name to \$allowed_faces in config/application.php";
      break;
    case 'controller':
      $face_name = $arguments[3]; if (is_null($face_name) || $face_name == '') { echo 'No face name specified'; die(); }
      $controller_name = $arguments[4]; if (is_null($controller_name) || $controller_name == '') { echo 'No controller name name specified'; die(); }
      $actions = array_slice($arguments, 5);

      $this->create_controller($face_name, $controller_name, $actions);
      echo "created controller $controller_name\r\n";
      break;
    case 'view':
      break;
    default:
      echo "Error: object must be one of:\r\n";
      foreach($legitimate_objects as $lo) {
          echo $lo."\r\n";
      }
    }
  }
  function create_controller($face_name, $controller_name, $actions) {
    global $path_to_root;

    /* create the face first if needed */
    if (!file_exists($path_to_root.'/'.$face_name)) {
      $result = $this->create_face($face_name);
      if (!$result) {
        return false;
      }
    }

    /* create the controller file */
    //todo DON'T bork the controller file
    file_put_contents(
      $path_to_root.'/'.$face_name.'/controllers/'.$controller_name.'_controller.php', 
      $this->controller_text($controller_name, $actions)
    );

    /* create the view files */


    return true;
  }

  private function controller_text($controller_name, $actions = array(), $extends = 'face_controller') {
    $controller_text =  '<'."?\r\nclass {$controller_name}_controller extends $extends {\r\n";

    //Actions
    if ($actions) {
      foreach ($actions as $action) {
        $controller_text .= "\r\n  protected function $action() {\r\n  }\r\n";
      }
    }

    $controller_text .= "\r\n}\r\n?>";

    return $controller_text;
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
    }

    /* if we've made it this far it means we can create directories */
    $face_root = $path_to_root.'/'.$face_name.'/';
    foreach ($face_structure as $folder) {
      mkdir($path_to_root.'/'.$face_name.'/'.$folder, 0777, true); //true is like mkdir -p
    }

    //create the face controller
    file_put_contents(
      $path_to_root.'/'.$face_name.'/controllers/face_controller.php',
      $this->controller_text('face', null, 'action_controller')
    );

    //TODO add face to config

    return true;
  }
}
?>
