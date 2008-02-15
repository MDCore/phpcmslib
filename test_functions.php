<?
/*
 * todo this code is copied verbatim from the migration code in system admin! Clean that up
 */

class pedantic_app_controller_Framework_TestCase extends PHPUnit_Framework_TestCase {
    public $controller_name = null;

    function __construct() {
        $controller_name = str_replace('_tests', '', get_class($this));
        $file_name = App::$env->root.'/'.pedantic_app_TestRunner::$face.'/controllers/'.$controller_name.'.php';
        /* require the controller */
        require_once($file_name);
        $this->controller_name = $controller_name;
    }

    protected function setUp()
    {
        $this->default_setup();
        session_destroy();
    }
    public function default_setup()
    {
        ob_start();
        require_once(app::$env->root.'/vendor/pedantic/lib/schema_interregator.php');
        require_once(app::$env->root.'/vendor/pedantic/system_admin/schema_migration.php');
        $sys = new schema_migration;
        $sys->get_latest_schema_number();
        $sys->allow_model_overwrite_override = true;

        $_POST = null; $_GET = null;
        #remigrate me!!
        #parse the migrations folder
        $path = App::$env->root.'/db/migrations';

        if ($handle = opendir(App::$env->root.'/db/migrations'))
        {
            $migrations = Array();
            while (false != ($file = readdir($handle)))
            {
                $file = $path."/$file";
                if (is_file($file))
                {
                    $migrations[] = $file;
                }
            }
            closedir($handle);
        }

        #sort the array
            sort($migrations);
        
        #build the meta-data
        for ( $i=0; $i < sizeof($migrations); $i++ )
        { 
            $file_name = file_name($migrations[$i]);
            $version = explode('_', $file_name); $version = $version[0]; 
            $description = str_replace('_', ' ', str_replace(file_extension($file_name), '', substr($file_name, strlen($version)+1)));
            $migrations[$i] = array(
                'version' => (int)$version,
                'filename' => $migrations[$i],
                'description' => $description,
                'extension' => file_extension($migrations[$i]),
            );
        }
        foreach ($migrations as $migration_file)
        {
            switch ($migration_file['extension'])
            {
            case 'sql':
                $sql_migration = file_get_contents($migration_file['filename']);
                execute_many_sql_statements($sql_migration, false);
                break;
            case 'php';
                    require($migration_file['filename']);
                break;
            }
            update_schema_version($migration_file['version']);
        }
        ob_end_clean();
    }
}
class pedantic_app_TestRunner 
{
    public static $parts = array('controllers', 'views');
    public static $test_files = array();
    public static $face;

    function init_face($path_to_root, $face)
    {
        //autodetect the face and load the face controller
        require_once ("$path_to_root/$face/controllers/face_controller.php");

        /* load all of the controller and view tests */
        foreach (pedantic_app_TestRunner::$parts as $part) {
            $test_files[$part] = find_part_tests($face, $part);
            foreach($test_files[$part] as $test_file) {
                require($test_file);
            }
        }
        pedantic_app_TestRunner::$test_files = $test_files;
        pedantic_app_TestRunner::$face = $face;
    }
    function init_models($path_to_root)
    {
        $part = 'models';
        /* load all of the controller and view tests */
        $test_files[$part] = find_part_tests($part);
        foreach($test_files[$part] as $test_file) {
            require($test_file);
        }
        pedantic_app_TestRunner::$test_files = $test_files;
    }
}

function find_part_tests($part, $face = null)
{
    if ($face) {
        /* face-specific parts */
        $path = App::$env->root."/$face/test/$part/";
    } else {
        /* app-general parts */
        $path = App::$env->root."/test/$part/";
    }
    if ($handle = opendir($path)) {
        $files = Array();
        while (false != ($file_name = readdir($handle))) {
            $file = $path.$file_name;
            if (is_file($file)) {
                $test_class_name = str_replace('.php', '', $file_name);
                $files[$test_class_name] = $file;
            }
        }
        closedir($handle);

        return $files;
    }
}

function update_schema_version($version)
{
    $sql = "UPDATE schema_info set version='$version'";
    $AR = new AR;
    $result = $AR->db->query($sql); AR::error_check($result);
}

function execute_many_sql_statements($sql_statements, $print_statements = true)
{
    $sql_statements = explode(';',$sql_statements);
    foreach ($sql_statements as $sql)
    {
        $sql = trim($sql);
        if ($sql != '')
        {
            if ($print_statements) {echo "<div><i>executing:</i><br />"; echo $sql;echo '</div>';}
            $AR = new AR;
            $result = $AR->db->query($sql); AR::error_check($result);
        }
    }
}

function file_extension($filename)
{
    $ext = explode('/', $filename);
    $ext = $ext[sizeof($ext)-1];
    $ext = explode('.', $ext);

    $ext = $ext[sizeof($ext)-1];
    return $ext;
}
function file_name($filename_with_path)
{
    $file_name = explode('/', $filename_with_path);
    $file_name = $file_name[sizeof($file_name)-1];
    return $file_name;
}
?>
