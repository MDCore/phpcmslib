<?
/*
 * todo this code is copied verbatim from the migration code in system admin! Clean that up
 */
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
