<?
/*
 * todo:
 * - fixtures CSV... yaml?
 */

/* TESTCASE CLASSES */
class pedantic_app_testsuite extends PHPUnit_Framework_TestSuite
{

}
/* the super class extends PHPUnit Framework Testcase and puts in the overrall methods for e.g. rebuilding the testing DB */
class pedantic_app_testcase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $schema = pedantic_app_testrunner::$schema;
        if ($schema != null) {
            /* recreate the database */
            pedantic_app_testrunner::recreate_database();
            /* load the schema into the database */
            $AR = new AR;
            $AR->db->loadModule('Manager');
            $AR->db->setDatabase(App::$env->dsn['database']);

            foreach ($schema as $table_name => $table_definition) {
                /* clean up the datable definition. The output from MDB2::Reverse does not fit with the input of MDB2::createTable */
                for ($i=0; $i < sizeof($table_definition); $i++) {
                    // get the field name
                    $field_name = $table_definition[$i]['name'];

                    // add the named definition (as opposed to the numbered one
                    $table_definition[$field_name] = $table_definition[$i];

                    // set some required properties of the definition
                    $table_definition[$field_name]['type'] = $table_definition[$field_name]['mdb2type'];

                    // remove unnecessary properties of the definition
                    foreach (array('table', 'nativetype', 'mdb2type', 'table', 'flags') as $property) {
                        unset($table_definition[$field_name][$property]);
                    }

                    unset($table_definition[$i]);
                }
                //#echo $table_name."\r\n";
                //var_dump($table_definition);
                $result = $AR->db->createTable($table_name, $table_definition);
            }
        }
    }
    /* the fixture name is based on the table name (plural) and not the model name (singular) */
    public function fixture($fixture)
    {
        global $path_to_root;
        $sys = new schema_migration;
        $sys->allow_model_overwrite = false;

        /* load the fixture */
        require("$path_to_root/test/fixtures/$fixture.php");

    }

}
/* this is for controllers and views and extends the pedantic app framework Testcase */
class pedantic_controller_view_testcase extends pedantic_app_testcase
{
    public $controller_name = null;

    function __construct() {
        /* todo this is only for controllers! */
        $this->get_part('controller', $this);
        $controller_name = str_replace('_tests', '', get_class($this));
        $file_name = App::$env->root.'/'.pedantic_app_testrunner::$face.'/controllers/'.$controller_name.'.php';
        /* require the controller */
        require_once($file_name);
        $this->controller_name = $controller_name;
    }
}
/* This is for models and extends the pedantic app framework Testcase */
class pedantic_app_model_testcase extends pedantic_app_testcase
{
    public $model_name = null;

    function __construct() {
        $model_name = str_replace('_tests', '', get_class($this));
        /* I don't need to include the models here because all tests includes them so that the controllers have access */
        $this->model_name = $model_name;
    }

}


/* TESTRUNNER CLASS */
class pedantic_app_testrunner extends PHPUnit_TextUI_TestRunner
{
    public static $parts = array('controllers', 'views');
    public static $test_files = array();
    public static $face;
    public static $schema = null;

    function init_face($path_to_root, $face)
    {
        //autodetect the face and load the face controller
        require_once ("$path_to_root/$face/controllers/face_controller.php");

        /* load all of the controller and view tests */
        foreach (pedantic_app_testrunner::$parts as $part) {
            $test_files[$part] = pedantic_app_testrunner::find_part_tests($face, $part);
            foreach($test_files[$part] as $test_file) {
                require($test_file);
            }
        }
        pedantic_app_testrunner::$test_files = $test_files;
        pedantic_app_testrunner::$face = $face;
    }
    function init_models($path_to_root)
    {
        $part = 'models';
        /* load all of the controller and view tests */
        $test_files[$part] = pedantic_app_testrunner::find_part_tests($part);
        foreach($test_files[$part] as $test_file) {
            require($test_file);
        }
        pedantic_app_testrunner::$test_files = $test_files;
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

    public function run_all_migrations()
    {
        /* runs all of the migrations against the db in the test env
         */
        $sys = new schema_migration;
        $sys->allow_model_overwrite = false;

        /* drop and recreate the database */
        echo "Running migrations\r\n";
        ob_start();
        pedantic_app_testrunner::recreate_database();
        for($i = 0; $i < sizeof($sys->migrations); $i++) {
            $migration = $sys->migrations[$i];

            $sys->run_migration($migration);
            //$sys->migrations[$i]['result'] = ob_get_clean();

            if (!$sys->schema_rebuilt) {
                $sys->rebuild_schema_definition();
            }
        }
        ob_end_clean();
        echo "Database recreated\r\n";
    }
    public function recreate_database() {
        $db_name = App::$env->dsn['database'];
        if ($db_name == '') {
            die('No database name specified in the test environment');
        }
        if (strpos($db_name, 'test') === FALSE) {
            die("The test environment database name needs to be named *test to ensure that it isn't using the same database as another environment.");
        }
        $AR = new AR;
        $AR->db->query("DROP DATABASE IF EXISTS $db_name");
        AR::error_check($AR->db);
        $AR->db->query("CREATE DATABASE $db_name");
        AR::error_check($AR->db);

        $sys = new schema_migration;
        ob_start();
        $sys->create_schema_info_table();
        ob_get_clean();
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
