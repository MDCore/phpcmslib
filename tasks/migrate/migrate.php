<?
/* todo
 * check for a parameter passed, a target migration
 * ask on remigrate! NB
*/

$path_to_lib = dirname(__FILE__).'/../..';
$path_to_root = $path_to_lib.'/../../..');

require($path_to_lib.'/init.php');
require($path_to_lib.'/schema_interregator.php');
require($path_to_lib.'/schema_migration.php');


/* check for shell */
if (isset($_SERVER['SHELL']) && !is_null($_SERVER['SHELL'])) {
    App::$running_from_shell = true;
    if ($argc > 1) {
        $_GET['remigrate'] = $argv[1];
    }
} else {
    App::$running_from_shell = false;
}

$sys = new schema_migration;
$sys->running_from_shell = App::$running_from_shell;

#get the latest migration number
    $schema_version = $sys->get_latest_schema_number();

    foreach ($sys->migrations as $migration) {
        if ($migration['version'] > $schema_version) {
                $sys->run_migration($migration);
        }
    }

    #successfully completed, output the results
    if (!App::$running_from_shell) {
        require($path_to_root.'/vendor/pedantic/lib/tasks/migrate/migrate_html_layout.php');
    }
?>
