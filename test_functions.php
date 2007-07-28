<?
/*
 * todo this code is copied verbatim from the migration code in system admin! Clean that up
 */

class test
{
    public function default_setup()
    {
    $_POST = null; $_GET = null;
    #remigrate me!!
    #parse the migrations folder
        $path = App::$env->root.'/db/migrations';

        if ($handle = opendir(App::$env->root.'/db/migrations'))
        {
            $migrations = Array();
            while (false != ($file = readdir($handle)))
            {
                $file = $path."/$file";
                if (is_file($file))
                {
                    $migrations[] = $file;
                }
            }
            closedir($handle);
        }

        #sort the array
            sort($migrations);
        
        #build the meta-data
        for ( $i=0; $i < sizeof($migrations); $i++ )
        { 
            $file_name = file_name($migrations[$i]);
            $version = explode('_', $file_name); $version = $version[0]; 
            $description = str_replace('_', ' ', str_replace(file_extension($file_name), '', substr($file_name, strlen($version)+1)));
            $migrations[$i] = array(
                'version' => (int)$version,
                'filename' => $migrations[$i],
                'description' => $description,
                'extension' => file_extension($migrations[$i]),
            );
        }
        foreach ($migrations as $migration_file)
        {
            switch ($migration_file['extension'])
            {
            case 'sql':
                $sql_migration = file_get_contents($migration_file['filename']);
                execute_many_sql_statements($sql_migration, false);
                break;
            case 'php';
                    require($migration_file['filename']);
                break;
            }
            update_schema_version($migration_file['version']);
        }
    }
}

function update_schema_version($version)
{
    $sql = "UPDATE schema_info set version='$version'";
    $AR = new AR;
    $result = $AR->db->query($sql); AR::error_check($result);
}

function execute_many_sql_statements($sql_statements, $print_statements = true)
{
    $sql_statements = explode(';',$sql_statements);
    foreach ($sql_statements as $sql)
    {
        $sql = trim($sql);
        if ($sql != '')
        {
            if ($print_statements) {echo "<div><i>executing:</i><br />"; echo $sql;echo '</div>';}
            $AR = new AR;
            $result = $AR->db->query($sql); AR::error_check($result);
        }
    }
}

function file_extension($filename)
{
    $ext = explode('/', $filename);
    $ext = $ext[sizeof($ext)-1];
    $ext = explode('.', $ext);

    $ext = $ext[sizeof($ext)-1];
    return $ext;
}
function file_name($filename_with_path)
{
    $file_name = explode('/', $filename_with_path);
    $file_name = $file_name[sizeof($file_name)-1];
    return $file_name;
}
?>
