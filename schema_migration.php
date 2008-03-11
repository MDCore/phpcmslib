<?
class schema_migration
{
    public $running_from_shell = false;
    public $allow_model_overwrite_override = false; /* testing sets this to true */
    function __construct() {
        $AR = new AR; $this->db = $AR->db;
        $this->load_migrations();
    }

    function get_latest_schema_number() {
        $sql = "SELECT version from schema_info";
        $result = $this->db->query($sql); 
        #check if the table exists
        if (MDB2_ERROR_NOSUCHTABLE == AR::error_check($result, false))
        {
            #create the table and reselect
            $this->create_schema_info_table();
            $result = $this->db->query($sql); 
            AR::error_check($result);
        }
        else
        {
            #die on the error
            AR::error_check($result);
        }

        $result = $result->fetchRow();
        if ($result && ! isset($_GET['remigrate']))
        {
            $schema_version = $result->version;
        }
        else
        {
            $schema_version = 0;
        }

        return $schema_version;
    }
    function load_migrations() {
        $path = App::$env->root.'/db/migrations';

        if ($handle = opendir(App::$env->root.'/db/migrations')) {
            $migrations = Array();
            while (false != ($file = readdir($handle))) {
                $file = $path."/$file";
                if (is_file($file))
                {
                    $migrations[] = $file;
                }
            }
            closedir($handle);
        } else {
            #todo die on error
        }

        //sort the array
            sort($migrations);
        
        //build the meta-data
            for ( $i=0; $i < sizeof($migrations); $i++ )
            { 
                $file_name = $this->file_name_from_full_path($migrations[$i]);
                $version = explode('_', $file_name); $version = $version[0]; 
                $description = str_replace('_', ' ', str_replace($this->file_extension_from_file_name($file_name), '', substr($file_name, strlen($version)+1)));
                $migrations[$i] = array(
                    'version' => (int)$version,
                    'filename' => $migrations[$i],
                    'description' => $description,
                    'extension' => $this->file_extension_from_file_name($migrations[$i]),
                );
            }
        $this->migrations = $migrations;
    }

    function run_migration($migration) {
        $sys = $this;
        switch ($migration['extension']) {
        case 'sql':
            $sql_migration = file_get_contents($migration['filename']);
            $this->execute_many_sql_statements($sql_migration);
            break;
        case 'php';
                require($migration['filename']);
            break;
        }
        $this->update_schema_version($migration['version']);

        // rebuild the schema definition
        //$schema_interregator_results = schema_interregator::build_schema_definition();
    }

    function update_schema_version($version) {
        $sql = "UPDATE schema_info set version='$version'";
        $AR = new AR;
        $result = $this->db->query($sql); AR::error_check($result);
    }
    function execute_many_sql_statements($sql_statements) {
        $sql_statements = explode(';',$sql_statements);
        foreach ($sql_statements as $sql)
        {
            $sql = trim($sql);
            if ($sql != '')
            {
                if (!$this->running_from_shell) {echo "<div><i>executing:</i><br />"; echo $sql;echo '</div>';}
                $AR = new AR;
                $result = $this->db->query($sql); AR::error_check($result);
            }
        }
    }
    function file_extension_from_file_name($filename) {
        $ext = explode('/', $filename);
        $ext = $ext[sizeof($ext)-1];
        $ext = explode('.', $ext);

        $ext = $ext[sizeof($ext)-1];
        return $ext;
    }
    function file_name_from_full_path($filename_with_path) {
        $file_name = explode('/', $filename_with_path);
        $file_name = $file_name[sizeof($file_name)-1];
        return $file_name;
    }
    function create_schema_info_table() {
        if (!$this->running_from_shell) {echo "creating schema_info table<br />";}

        $this->table('schema_info', array(
            array('version', 'integer', array('not_null' => true, 'default' => 0))
            )
        );

        /* add the schema info row */
        $schema_info_data = array('version' => '0');
        $this->db->loadModule('Extended');
        $result = $this->db->autoExecute('schema_info', $schema_info_data, MDB2_AUTOQUERY_INSERT);
        AR::error_check($result);
    }

    function pull_table_schema($table_name) { #stolen somewhat from schema_interregator
        $this->db->loadModule('Reverse', null, true);
        $table_schema = $this->db->tableInfo($table_name, null);
        $error_code = AR::error_check($table_schema, false);
        #check if there were any errors pulling the schema
        if ($error_code) 
        {
            switch ($error_code)
            {
            case -18:
                $message = "$table_name not found"; break;
            default:
                $message = '('.$error_code.') '.$table_schema->getMessage();
            }
            trigger_error($message, E_USER_WARNING);
        }
        return $table_schema;
    }
    function table() {
        $arguments = func_get_args();

        //pull the table and schema from args
        $table_name = $arguments[0];
        $schema_definition = $arguments[1];
        //var_dump($table_name);
    
        $AR = new AR;
        $manager = $this->db->loadModule('Manager');
        $mdb2_table = array();
        // add the primary key
        $mdb2_table['id'] = array('type' => 'integer', 'notnull' => true, 'autoincrement' => true);

        /* convert our compact schema definition syntax into the mdb2 syntax */
        foreach ($schema_definition as $field) {
            if (is_array($field)) { /* this is a specified field and not a shorcut */
                $field_name = $field[0];
                $type = $field[1];
                #convert our type names to mdb2 type names
                switch ($field[1]) {
                case 'string':
                    $type = 'text';
                    $field[2]['length'] = 255;
                    break;
                case 'text':
                    $type = 'clob'; break;
                case 'datetime':
                    $type = 'timestamp'; break;
                case 'foreign_key':
                    $type = 'integer';
                    $field_name = foreign_keyize($field_name);
                    break;
                default:
                    $type = $field[1];
                }

                $mdb2_table[$field_name] = array('type' => $type);

                //deal with the additional options
                if (sizeof($field) > 2) {
                    $as = $field[2];
                    if (isset($as['default'])) { $mdb2_table[$field_name]['default'] = $as['default']; }
                    if (isset($as['length'])) { $mdb2_table[$field_name]['length'] = $as['length']; }
                    if (isset($as['not_null'])) { $mdb2_table[$field_name]['notnull'] = 1; }
                }
            } else {
                #some shortcuts
                switch ($field) {
                case 'timestamps':
                    #create the timestamps fields
                    $mdb2_table['created_on'] = array('type' => 'timestamp', 'notnull' => true);
                    $mdb2_table['updated_on'] = array('type' => 'timestamp', 'notnull' => true);
                }
            }
        }

        //var_dump($mdb2_table);
        //drop the table
        try {
            $result = $manager->dropTable($table_name); AR::error_check($result);
            echo "dropped table $table_name<br />";
        }
        catch (Exception $e) {
            #print_r($e);
        }
        #create the table
        echo "creating table $table_name. ";
        $result = $manager->createTable($table_name, $mdb2_table); AR::error_check($result);

        $definition = array(
            'primary' => true,
            'fields' => array(
                'id' => array()
            )
        );

        //reset the auto_increment
        $sql_pk = "ALTER TABLE {$this->db->database_name}.$table_name AUTO_INCREMENT=1";
        $result = $this->db->query($sql_pk); AR::error_check($result);

        //add the pk constraint
        $this->db->createConstraint($table_name, $table_name.'_primary_key', $definition);
        echo "created table $table_name.<br />";

        // rebuild the schema definition
        $schema_interregator_results = schema_interregator::build_schema_definition();

        flush();
    }
    
    /* TODO do it in 'html' then capture + save */
    function create_model($model_name, $schema = null, $options = null, $allow_overwrite = false) {
        global $path_to_root;
        #by default these models extend AR. extending something else means some hand-coding. convention over configuration

        /*
         * options
         *      changelog, acts_as_nested_set
         *      has_one, has_many, $belongs_to
         *      display_field
         *      validates_presence_of
         */

        if (!$this->allow_model_overwrite_override) { /* testing bypasses the file creation */
            #create the file if required
            $filename = realpath($path_to_root)."/models/$model_name.php";

            #check if the file exists
            $file_exists = (file_exists($filename));
            if ( ($file_exists && $allow_overwrite) || !$file_exists || ($file_exists && (filesize($filename) == 0) ) )
            {
                #generate the source
                /* header */
                $source = '<'."?\r\n/* This file was automatically generated by pedantic_lib "/*on ".strftime(SQL_INSERT_DATE_TIME_FORMAT, time())*/."*/\r\n";
                $source .= "\r\nclass $model_name extends ";
                if ($options && isset($options['extends']))
                {
                    $source .= $options['extends'];
                }
                else
                {
                    $source .= 'AR';
                }
                $source .= " {\r\n";
                if ($options && sizeof($options) > 0)
                {
                    foreach ($options as $option => $value)
                    {
                        #breaking it down so that we can re_use options to specify things that are not just properties of the class
                        switch ($option)
                        {
                        case 'extends':
                            break;
                        default:
                            if ($value && $option)
                            {
                                $source .= "\tpublic $"."$option = '$value';\r\n";
                            }
                            else
                            {
                                $source .= "\tpublic $"."$value;\r\n";
                            }
                        }
                    }
                }
                $source .= "}\r\n".'?'.'>';

                #echo $filename;die();

                if (!file_put_contents($filename, $source))
                {
                    trigger_error("The model file could not be created please: please create these files manually as empty files in order to use this functionality", E_USER_ERROR);
                }
                else
                {
                    if (!isset($_SESSION[APP_NAME]['application']['models']["$model_name.php"]))
                    {
                        #pop this model into the session
                        $_SESSION[APP_NAME]['application']['models']["$model_name.php"] = $filename;
                        #load it
                        require($path_to_root."/models/$model_name.php");
                    }
                }
                #print_r($_SESSION[APP_NAME]);die();
            }
        }

        #execute the schema creation
        #todo allow overriding the table_name
        if (substr($model_name, -10) == '_changelog') { $table_name = pluralize(substr($model_name, 0, strlen($model_name) -10)).'_changelog'; } else { $table_name = pluralize($model_name); }
        if ($options && (isset($options['acts_as_nested_set']) || in_array('acts_as_nested_set', array_values($options)))) {
            // append the nested_set schema stuff
            $schema[] = array('ns_root_id', 'integer');
            $schema[] = array('ns_parent_id', 'integer');
            $schema[] = array('ns_left_id', 'integer');
            $schema[] = array('ns_right_id', 'integer');
            $schema[] = array('ns_node_order', 'integer');
            $schema[] = array('ns_level', 'integer');
        }
        $this->table($table_name, $schema);

        #update app::schema_definition
        $schema_definition = schema_interregator::pull_schema_for_model($model_name);
        //var_dump($schema_definition); die();
        App::$schema_definition[$model_name] = $schema_definition;

        #create a changelog model and schema, if this model has a changelog
        if ($options && (isset($options['changelog']) || in_array('changelog', array_values($options)))) {
            $changelog_schema = $schema;
            $changelog_schema[] = array($model_name.'_id', 'integer');
            $changelog_schema[] = array('revision', 'integer');
            $changelog_schema[] = array('action', 'string', array('length' => 50));

            $this->create_model($model_name.'_changelog', $changelog_schema, null, $allow_overwrite);
        }
        #acts_as_nested_set: create the _locks table
        if ($options && (isset($options['acts_as_nested_set']) || in_array('acts_as_nested_set', array_values($options)))) {
            $aans_schema[] = array('lockID', 'string');
            $aans_schema[] = array('lockTable', 'string');
            $aans_schema[] = array('lockStamp', 'string');
            $this->table(pluralize($model_name).'_locks', $aans_schema);
        }
    }
}
?>
