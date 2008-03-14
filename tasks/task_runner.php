<?
/* TODO
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
case 'help':
?>
usage:
beachhead <path> <repository_url> [--root_url=ROOT_URL]     Create a new project
migrate [--remigrate] [--force_from=REVISION]               Run migrations
<?
    break;
default:
    die('No task specified or unknown task');
}
?>
