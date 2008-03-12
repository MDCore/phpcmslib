#!/usr/bin/php
<?
/* pull in the args */
$task_name = $argv[1];
/*
 * getting arguments, later
for ($i = 1; $i < sizeof($argv); $i++) {
}*/

$only_require_libraries = true;
$path_to_lib = dirname(__FILE__).'/..';
require $path_to_lib.'/init.php';
$path_to_root = $path_to_lib.'/../../..';

/* todo, the environment?!?!? */

switch ($task_name) {
case 'beachhead':
    require $path_to_lib.'/tasks/beachhead.php';
    $beachhead = new tasks_beachhead;
    $beachhead->output_progress = true;
    $beachhead->run($argv);
    break;
case 'migrate':
    $_GET['remigrate'] = $argv[2];
    require $path_to_lib.'/schema_interregator.php' ;
    require $path_to_lib.'/schema_migration.php' ;
    require $path_to_lib.'/tasks/migrate/migrate.php';
    break;
}

?>
