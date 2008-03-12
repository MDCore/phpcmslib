<?
/* TODO
 * none at the moment
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
    $_GET['remigrate'] = $argv[2];
    require $path_to_lib.'/schema_interregator.php' ;
    require $path_to_lib.'/schema_migration.php' ;
    require $path_to_lib.'/tasks/migrate/migrate.php';
    break;
default:
    die('No task specified or unknown task');
}
?>
