<?
class tasks_test implements lib_task
{
  public function __construct() {
  }

  public function run($arguments) {
    ini_set('memory_limit', '100M');
    require_once 'PHPUnit/Framework.php';
    require_once 'PHPUnit/TextUI/TestRunner.php';

    /* setup test_specific stuff */
    $path_to_root = '.';
    define('TEST_MODE', true);

    require $path_to_root.'/vendor/pedantic/lib/init.php';
    require $path_to_lib.'/schema_interregator.php' ;
    require $path_to_lib.'/schema_migration.php' ;
    require $path_to_root.'/vendor/pedantic/lib/test_functions.php';

    /* include the helpers file with general custom test methods */
    include $path_to_root.'/test/test_helpers.php';

    /* pull the development database schema */
    // step 1: run all the migrations against the test environment (implicitly the test env)
    echo "Running the migrations against the test database\r\n";
    pedantic_app_testrunner::run_all_migrations();

    // step 2: pull the schema
    echo "pulling the schema from the test database\r\n";
    $schema_interregator = new schema_interregator;
    $schema = $schema_interregator->pull_entire_schema(App::$env->dsn);

    /* set the schema property in pedantic_app_testrunner */
    pedantic_app_testrunner::$schema = $schema;

    /* run all the tests */
    pedantic_app_testrunner::run_tests();
  }

  public function help() {
?>
Test : Run tests
================
usage:
test
<?
  }
}
?>
