<?
/* TODO
 * - ask on remigrate! NB
*/

class tasks_migrate implements lib_task
{
  public function __construct() {
    global $path_to_lib;
    require $path_to_lib.'/schema_interregator.php' ;
    require $path_to_lib.'/schema_migration.php' ;
}
  public function run($arguments) {
    global $path_to_lib;

    $sys = new schema_migration;
    $sys->running_from_shell = App::$running_from_shell;

    //get the latest migration number
    if (!in_array('remigrate', $arguments)) {
      $schema_version = $sys->get_latest_schema_number();
    } else {
      $schema_version = 0;
    }
    if (!$sys->running_from_shell) {
    } else {
        echo 'Schema currently at version '.$schema_version;
        echo "\r\n";
    }

    if (isset($arguments['force_from'])) {
        $force_from = $arguments['force_from'];
    }

    for($i = 0; $i < sizeof($sys->migrations); $i++) {
        $migration = $sys->migrations[$i];

        if (($migration['version'] > $schema_version) || (isset($force_from) && $force_from <= $migration['version'])) {
            ob_start();
            $sys->run_migration($migration);
            $sys->migrations[$i]['result'] = ob_get_clean();
            if ($sys->running_from_shell) {
                echo $sys->migrations[$i]['result'];
            }
        }
        if (!$sys->schema_rebuilt) {
            $sys->rebuild_schema_definition();
        }
    }

    //successfully completed, output the results
    if (!$sys->running_from_shell) {
        require($path_to_lib.'/tasks/migrate/migrate_html_layout.php');
    } else {
        echo 'Migration completed at version '.$migration['version'];
        echo "\r\n";
    }
  }
  public function help() {
?>
Migrate : Run migrations
========================
usage:
migrate
[--remigrate]                   Run all migrations from the beginning. WARNING this
                                is dangerous!
[--force_from=REVISION]         The default process is to run any new migrations
                                starting after the number stored in the schema_info
                                table. This runs from an arbitrary migration number.
<?
  }
}
?>
