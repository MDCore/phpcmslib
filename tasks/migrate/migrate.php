<?
/* todo
 * check for a parameter passed, a target migration
 * ask on remigrate! NB
*/



/* check for shell */
if (isset($_SERVER['SHELL']) && !is_null($_SERVER['SHELL'])) {
} else {
    $path_to_lib = dirname(__FILE__).'/../..';
    $path_to_root = $path_to_lib.'/../../..';

    require($path_to_lib.'/init.php');
    require($path_to_lib.'/schema_interregator.php');
    require($path_to_lib.'/schema_migration.php');

    App::$running_from_shell = false;
}

$sys = new schema_migration;
$sys->running_from_shell = App::$running_from_shell;

#get the latest migration number
$schema_version = $sys->get_latest_schema_number();

for($i = 0; $i < sizeof($sys->migrations); $i++) {
    $migration = $sys->migrations[$i];

    if ($migration['version'] > $schema_version) {
        ob_start();
        $sys->run_migration($migration);
        $sys->migrations[$i]['result'] = ob_get_clean();
    }
}

    #successfully completed, output the results
    if (!App::$running_from_shell) {
        require($path_to_root.'/vendor/pedantic/lib/tasks/migrate/migrate_html_layout.php');
    }
?>
