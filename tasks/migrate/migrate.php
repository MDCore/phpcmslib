<?
/* TODO
 * - ask on remigrate! NB
 * - check for a parameter passed, a target migration
*/

$sys = new schema_migration;
$sys->running_from_shell = App::$running_from_shell;

//get the latest migration number
$schema_version = $sys->get_latest_schema_number();

for($i = 0; $i < sizeof($sys->migrations); $i++) {
    $migration = $sys->migrations[$i];

    if ($migration['version'] > $schema_version) {
        ob_start();
        $sys->run_migration($migration);
        $sys->migrations[$i]['result'] = ob_get_clean();
    }
}

//successfully completed, output the results
if (!isset(App::$running_from_shell) || !App::$running_from_shell) {
    require($path_to_root.'/vendor/pedantic/lib/tasks/migrate/migrate_html_layout.php');
}
?>
