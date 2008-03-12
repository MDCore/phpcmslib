<?
/* TODO
 * - fix bug with running migrate from shell (it won't work at all, app env not loading!?!?
 * - do $_GET's when run from browser
 */

/* pull in the args */
if (isset($_SERVER['SHELL'])) {

    /* running from the shell */
    $task_name = $argv[1];
    $arguments = $argv;
    $running_from_shell = true;

} else {

    /* running from the browser */
    $task_name = $_GET['task'];
    $arguments = array($task_name, $_GET['remigrate']);
    $running_from_shell = true;
}

/*
 * getting arguments, later
for ($i = 1; $i < sizeof($argv); $i++) {
}*/

$path_to_lib = dirname(__FILE__).'/..';
$path_to_root = $path_to_lib.'/../../..';

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
}

?>
