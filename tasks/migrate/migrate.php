<?
/* TODO
 * - ask on remigrate! NB
 * - check for a parameter passed, a target migration
*/

$sys = new schema_migration;
$sys->running_from_shell = App::$running_from_shell;

//get the latest migration number
$schema_version = $sys->get_latest_schema_number();
if (!$sys->running_from_shell) {
} else {
    echo 'Schema currently at version '.$schema_version;
    echo "\r\n";
}

for($i = 0; $i < sizeof($sys->migrations); $i++) {
    $migration = $sys->migrations[$i];

    if ($migration['version'] > $schema_version) {
        ob_start();
        $sys->run_migration($migration);
        $sys->migrations[$i]['result'] = ob_get_clean();
        if ($sys->running_from_shell) {
            echo $sys->migrations[$i]['result'];
        }
    }
}

//successfully completed, output the results
if (!$sys->running_from_shell) {
    require($path_to_root.'/vendor/pedantic/lib/tasks/migrate/migrate_html_layout.php');
} else {
    echo 'Migration completed at version '.$migration['version'];
    echo "\r\n";
}
?>
