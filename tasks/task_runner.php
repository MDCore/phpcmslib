<?
/* TODO
 * - nicer output for tests. Print out each model, controller etc as its tests are being run
 *  - this probably means splitting it into seperate suites ?
 */

/* NOTE
 * This script should be shell/web agnostic.
 */

$path_to_lib = dirname(__FILE__).'/..';
$path_to_root = $path_to_lib.'/../../..';


/* check if running directly from lib. The beachhead does this */
if (!isset($task_name) || $task_name == '') {
    $task_name = $argv[1];
    $arguments = $argv;
    $running_from_shell = true;
}

/* clean up the arguments */
if ($running_from_shell) {
    $arguments_for_loop = $arguments;
    $arguments = array();
    foreach ($arguments_for_loop as $key => $value) {
        /* remove the -- from parameters */
        if (substr($value, 0, 2) == '--') {
            $value = substr($value, 2);
        }
        /* split the key=value items into key and value if applicable
         * otherwise leave it alone
         */
        $key_value = explode('=', $value, 2);
        if (sizeof($key_value) > 1) {
            $arguments[$key_value[0]] = $key_value[1];
        } else {
            $arguments[$key] = $value;
        }

    }
}

/* $task_name and $arguments come from the shell or web task script */
switch ($task_name) {

case 'beachhead':
    /* init */
    $only_require_libraries = true; /* this doesn't need (or want) config or environment */
    require $path_to_lib.'/init.php';

    require $path_to_lib.'/tasks/beachhead.php';
    $beachhead = new tasks_beachhead;
    $beachhead->output_progress = true;
    $beachhead->run($arguments);
    break;

case 'migrate':
    /* init */
    require $path_to_lib.'/init.php';

    App::$running_from_shell = $running_from_shell;
    require $path_to_lib.'/schema_interregator.php' ;
    require $path_to_lib.'/schema_migration.php' ;
    require $path_to_lib.'/tasks/migrate/migrate.php';
    break;

case 'test':
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

    break;
case 'create':
    /* init */
    require $path_to_lib.'/init.php';

    App::$running_from_shell = $running_from_shell;
    require $path_to_lib.'/tasks/create.php';

    $create = new tasks_create;
    $create->run($arguments);
    break;

case 'help':
?>
Beachhead : Create a new project
=========
usage:
beachhead <path> <repository_url>
[--root_url=ROOT_URL]           The default root URL is '/'.
[--project=PROJECT_NAME]        The default project name is the last directory in
                                the path parameter.

Migrate : Run migrations
=======
usage:
migrate
[--remigrate]                   Run all migrations from the beginning. WARNING this
                                is dangerous!
[--force_from=REVISION]         The default process is to run any new migrations
                                starting after the number stored in the schema_info
                                table. This runs from an arbitrary migration number.

Test : Run tests
=======
usage:
test

Create : Create objects
=======
usage:
To create a face:
create face <name>

To create a controller:
create controller <face name> <controller name> <action 1> <action 2> .... <action n>

<?
    break;
default:
    die('No task specified or unknown task');
}
?>
