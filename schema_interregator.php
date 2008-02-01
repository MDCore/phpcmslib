<?
class schema_interregator
{
    function pull_schema_for_model($model_name, $echo_progress = false)
    {
        if (substr($model_name, 0, 1) != '_')  { /* don't try to interrogate or load models prefixed with _ */
            $model_object = new $model_name;
            if (!isset($model_object->virtual))
            {
                $model_object->connect_to_db();
                #load the appropriate mdb2 modules
                    $model_object->db->loadModule('Reverse', null, true);

                #using the magic of mdb2's Reverse module and the method tableInfo
                $table_name = $model_object->dsn['database'].'.'.$model_object->schema_table;
                if ($echo_progress) { echo "writing schema of table <i>$table_name</i> for model <i>$model_name</i><br />"; }
                $table_schema = $model_object->db->tableInfo($table_name, null);
                $error_code = AR::error_check($table_schema, false);
                #check if there were any errors pulling the schema
                if ($error_code) 
                {
                    switch ($error_code)
                    {
                        /* this table name might just not exist yet */
                        case -18:
                            $message = "$table_name table not found"; break;
                        default:
                            $message = '('.$error_code.') '.$table_schema->getMessage();
                            trigger_error($message, E_USER_WARNING);
                    }
                }
                else
                {
                    #print_r($table_schema);
                
                    foreach ($table_schema as $field)
                    {
                        $fields_in_table[$field['name']] = array(
                            'type' => $field['type'],
                            'mdb2type' => $field['mdb2type'],
                            'length' => $field['length'],
                            'default' => $field['default'],
                            );
                    }
                    return $fields_in_table;
                }
            }
        }
    }

    function pull_schema()
    {
        $tables = null;

        foreach ($_SESSION[APP_NAME]['application']['models'] as $model_name => $model)
        {
            $fields_in_table = null;

            $model_name = str_replace('.php', '', $model_name);
            $fields_in_table = schema_interregator::pull_schema_for_model($model_name, true);
            if ($fields_in_table) { $tables[$model_name] = $fields_in_table; }
        }
        #echo '<pre>';print_r($tables);echo '</pre>';
        return $tables;           
    }

    function generate_schema_source($schema)
    {
        $source = null;
        $source = '$schema_definition = Array(';
        if (!$schema) { return false; } #todo raise an exception
        foreach ($schema as $model_name => $fields)
        {
            $source .= '\''.$model_name.'\' => Array('."\r\n\t";
            foreach ($fields as $field_name => $field_meta_data)
            {
                $source .= "'$field_name' => Array(\r\n";
                foreach ($field_meta_data as $md_name => $md_value)
                {
                    $md_value = "'$md_value'";
                    if (!$md_value) {$md_value = "null";}
                    $source .= "\t\t'$md_name' => $md_value,\r\n";
                }
                if (substr($source, -3) == ",\r\n") {$source = substr($source, 0, strlen($source)-3);$source .= "\r\n";}

                $source .= "\t),\r\n\t";
            }
            if (substr($source, -4) == ",\r\n\t") {$source = substr($source, 0, strlen($source)-4);}
            $source .= "\r\n),\r\n";
        }
        if (substr($source, -3) == ",\r\n") {$source = substr($source, 0, strlen($source)-3);}
        $source .= ");\r\n";
        return '<?'.$source.'?>';
        #echo '<pre>'.$source.'</pre>';
    }

    function write_schema_source($source)
    {
        if ($source == '') { return false; }
        global $path_to_root;

        $filename = realpath($path_to_root).'/config/cache/schema_definition.php';
        #echo $filename;

        #touch($filename);
        if (!file_put_contents($filename, $source))
        {
            trigger_error("<i>config/cache/schema_definition.php</i> could not be written to: please create this file manually and give the webserver write rights", E_USER_ERROR);
        }
    }

    function build_schema_definition()
    {
        $schema = schema_interregator::pull_schema();
        if ($schema == '')
        {
            trigger_error("The schema could not be pulled from the database. This is most likely because there are no models for the application", E_USER_ERROR);
        }
        $source = schema_interregator::generate_schema_source($schema);
        schema_interregator::write_schema_source($source);
    }
}
?>
