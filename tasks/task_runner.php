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

switch ($task_name) {
case 'beachhead':
    require $path_to_lib.'/tasks/beachhead.php';
    $beachhead = new tasks_beachhead;
    $beachhead->output_progress = true;
    $beachhead->run($argv);
    break;
case 'migrate':
    require $path_to_lib.'/tasks/migrate/migrate.php';
    break;
}

?>
