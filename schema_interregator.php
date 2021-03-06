<?
class schema_interregator
{
    function pull_entire_schema($env)
    {
        /* get a list of tables in the db */
        $AR = new AR;
        $AR->db->loadModule('Manager');
        $AR->db->loadModule('Reverse');
        $tables_list = $AR->db->listTables();

        /* pull each tables' schema */
        $tables = array();
        foreach ($tables_list as $table) {
            $table_name = $env['database'].'.'.$table;
            $table_schema = $AR->db->tableInfo($table_name, null);
            $tables[$table] = $table_schema;
        }
        return $tables;
    }

    function pull_schema_for_model($model_name, $echo_progress = false)
    {
        if (substr($model_name, 0, 1) != '_')  { /* don't try to interrogate or load models prefixed with _ */
            $model_object = new $model_name;
            if (!isset($model_object->virtual)) {
                $model_object->connect_to_db();
                //load the appropriate mdb2 modules
                    $model_object->db->loadModule('Reverse', null, true);

                //using the magic of mdb2's Reverse module and the method tableInfo
                $table_name = $model_object->dsn['database'].'.'.$model_object->schema_table;
                $table_schema = $model_object->db->tableInfo($table_name, null);
                $error_code = AR::error_check($table_schema, false);
                #check if there were any errors pulling the schema
                if ($error_code) {
                    switch ($error_code) {
                    /* this table name might just not exist yet, so don't die on this error */
                    case -18:
                        $message = "$table_name table not found";
                        break;
                    default:
                        $message = '('.$error_code.') '.$table_schema->getMessage();
                        trigger_error($message, E_USER_WARNING);
                    }
                } else {
                    //print_r($table_schema);

                    foreach ($table_schema as $field) {
                        $fields_in_table[$field['name']] = array(
                            'type' => $field['type'],
                            'mdb2type' => $field['mdb2type'],
                            'default' => $field['default'],
                            );
                        if (isset($field['length'])) {
                            $fields_in_table[$field['name']]['length'] = $field['length'];
                        }
                    }
                    return $fields_in_table;
                    if ($echo_progress) {
                        echo "writing schema of table <i>$table_name</i> for model <i>$model_name</i><br />";
                    }
                }
            }
        }
    }
    function pull_schema_for_all_models()
    {
        $tables = null;

        if (sizeof($_SESSION[APP_NAME]['application']['models']) > 0) {
            foreach ($_SESSION[APP_NAME]['application']['models'] as $model_name => $model) {
                $fields_in_table = null;

                $model_name = str_replace('.php', '', $model_name);
                $fields_in_table = schema_interregator::pull_schema_for_model($model_name, true);
                if ($fields_in_table) {
                    $tables[$model_name] = $fields_in_table;
                }
            }
        } else {
            trigger_error("The schema could not be pulled from the database. This is most likely because there are no models for the application", E_USER_ERROR);
        }
        //echo '<pre>';print_r($tables);echo '</pre>';

        return $tables;
    }

    function build_schema_definition()
    {
        $schema = schema_interregator::pull_schema_for_all_models();
        if ($schema != '') {
            $source = schema_interregator::generate_schema_source($schema);
            schema_interregator::write_schema_source($source);
        }
    }
    function generate_schema_source($schema)
    {
        $source = null;
        $source = '$schema_definition = Array(';
        if (!$schema) { return false; } //todo raise an exception
        foreach ($schema as $model_name => $fields) {
            $source .= '\''.$model_name.'\' => Array('."\r\n\t";
            foreach ($fields as $field_name => $field_meta_data) {
                $source .= "'$field_name' => Array(\r\n";
                foreach ($field_meta_data as $md_name => $md_value) {
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
        //echo '<pre>'.$source.'</pre>';
    }

    function write_schema_source($source, $filename = 'default')
    {
        if ($source == '') {
            return false;
        }
        global $path_to_root;

        if ($filename == 'default') { $filename = '/config/cache/schema_definition.php'; }
        $filename = realpath($path_to_root).$filename;
        #echo $filename;

        if (!file_put_contents($filename, $source)) {
            trigger_error("<i>config/cache/schema_definition.php</i> could not be written to: please create this file manually and give the webserver write rights", E_USER_ERROR);
        }
    }

}
?>
