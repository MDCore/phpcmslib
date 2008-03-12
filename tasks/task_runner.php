<?
/* TODO
 * - fix bug with running migrate from shell (it won't work at all, app env not loading!?!?
 * - do $_GET's when run from browser
 */

var_dump($_GET);die();

/* pull in the args */
if (isset($_SERVER['shell']) {
    /* running from the shell */
    App::$running_from_shell = true;
    $task_name = $argv[1];
    $arguments = $argv;
} else {
    /* running from the browser */
    App::$running_from_shell = false;
    $task_name = $_GET['task'];
    $arguments = ($task_name, $_GET['remigrate']);
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

    $_GET['remigrate'] = $argv[2];
    require $path_to_lib.'/schema_interregator.php' ;
    require $path_to_lib.'/schema_migration.php' ;
    require $path_to_lib.'/tasks/migrate/migrate.php';
    break;
}

?>
