<?php
/**
 * This file implements the ActiveRecord pattern
 * @author Gavin van Lelyveld <gavin@pedantic.co.za>
 * @version 0.2
 * @package pedantic/lib
 **/

if (!defined('SQL_INSERT_DATE_FORMAT')) { define('SQL_INSERT_DATE_FORMAT', '%Y-%m-%d'); }
if (!defined('SQL_INSERT_DATE_TIME_FORMAT')) { define('SQL_INSERT_DATE_TIME_FORMAT', '%Y-%m-%d %R'); }

class AR implements SeekableIterator { # basic AR class
    public $dirty = false;
    public $new = true;
    public $count = 0;
    public $validation_result = null; public $validation_errors = null;
    public $preserve_updated_on = false;
    public $db = null, $schema_definition;
    public $dsn;

    /* AR related fields - now that __get forbids getting random properties, these can be here as settable but not gettable */    
    /*
        public $model_name, $primary_key_field, $schema_table, $display_field, ;
        public $has_changelog;
     */
    public $has_one, $has_many, $has_many_through, $belongs_to;

    public $last_sql_query, $last_finder_sql_query;
    private $offset = 0;
    private $results;
    private $values = array();

    /* 
     * this method is here so that is is overridable
     */
    function connect_to_db($dsn = null) {
        if (!$dsn && isset(App::$env)) {$dsn = App::$env->dsn;}
        if ($dsn)
        {
            #debug("connecting to ".$dsn['database']);
            $this->dsn = $dsn;
            $this->db =& MDB2::singleton($dsn);
            #print_r($this->db);
            $this->error_check($this->db);
        }
        else
        {
            #raise an exception here ?
        }

        if ($this->db) { $this->db->setFetchMode(MDB2_FETCHMODE_OBJECT); }
    }

    /*
     *  this method pulls in the schema definition and creates the attributes in the object, setting them to null
     */
    function setup_attributes() {
        if (!isset(App::$schema_definition[$this->model_name])) { return false; }

        $this->schema_definition = App::$schema_definition[$this->model_name];
        $this->clear_attributes();
    }

    function __destruct() {
        unset($this->db);
        unset($this->values);
    }
    function __construct($collection = null, $with_value_changes = true)
    {
        if (!$this->db) { $this->connect_to_db(); }

        #debug echo "<b>before setting pk</b><br>\r\n";
        if (!property_exists($this, 'primary_key_field')) {$this->primary_key_field = 'id';}
        #debug echo "<b>after setting pk</b><br>\r\n\r\n";

        #get the model name
        $this->model_name = get_class($this);
        #debug echo "<b>after setting_model_name</b><br>\r\n\r\n";
       
        #pull in the schema definition, and set the attributes to null
            $this->setup_attributes();
        #debug echo('<b>after setup_attribs</b><br>');
        
        #set the primary table, checking first if this model_name is a changelog
        if (!property_exists($this, 'schema_table')) {
            $changelog_pos = strpos($this->model_name, '_changelog');
            if ($changelog_pos > 0) {
                #check if the parent model has a schema_table and use it instead of inferring the table name from the parent model name
                    $parent_model_name = substr($this->model_name, 0, $changelog_pos);
                    $parent_model_name = new $parent_model_name;
                    if (isset($parent_model_name->schema_table)) {
                        $this->schema_table = $parent_model_name->schema_table.'_changelog'; 
                    } 
                    else {
                        $this->schema_table = tableize(pluralize(substr($this->model_name, 0, $changelog_pos))).'_changelog';
                    }
                    unset($parent_model_name);
            }
            else {
                $this->schema_table = tableize(pluralize($this->model_name));
            }
        }
        #debug echo('<b>after setting primary table and stuffs</b><br>');

        #set that this model has a changelog
            if (property_exists($this->model_name, 'changelog')){ $this->has_changelog = true; unset($this->changelog); } else {$this->has_changelog = false; }

        #set the display field
            if (!property_exists($this, 'display_field')) {
                if (isset($this->schema_definition['title'])) { $this->display_field = 'title'; }
                elseif (isset($this->schema_definition['name'])) { $this->display_field = 'name'; }
                else { $this->display_field = 'id'; }
            }
        #debug echo('<b>after setting display_field</b><br>');

        #split the validations - todo. maybe use getobjectvars? todo add all validations
            $validations = array('validates_presence_of');
            foreach ($validations as $validation) {
                if (property_exists($this, $validation)) {
                    $this->$validation = split(',',$this->$validation);
                }
            }
        #debug echo('<b>after validation setup</b><br>');

        if ($collection) {$this->update_attributes($collection, $with_value_changes);} #updates attribs if object is created with a collection
        #die('end of construction');
    }

    /* 
     * this method handles dynamic finders, also known as magic methods.
     * an example would be: customer->find_by_firstname_and_lastname('john', 'smith');
     */
    private function __call($method_name, $params) {
        #overload finders
        $finder_criteria_pos = null; $finder_type = null;
        if (substr($method_name, 0, 8) == 'find_by_') { $finder_criteria_pos = 8; }
        if (substr($method_name, 0, 20) == 'find_most_recent_by_') { $finder_criteria_pos = 20; $finder_type = 'most recent'; }
        if (substr($method_name, 0, 11) == 'find_first_by_') { $finder_criteria_pos = 11; $finder_type = 'first'; }
        
        if ($finder_criteria_pos)
        {
            $find_by = substr($method_name, $finder_criteria_pos);
            $find_by = explode('_and_', $find_by);
            $finder_criteria = '';
            $cnt = 0;
            foreach ($find_by as $finder_criterion)
            {
                $finder_criteria.= $finder_criterion." = '".$params[$cnt]."' AND ";
                $cnt++;
            }
            if ($cnt > 0 ) { $finder_criteria = '('.substr($finder_criteria, 0, strlen($finder_criteria)-5).')'; }
            
            #extra params for special finders
            if (is_array($params[$cnt])) { $additional_criteria = $params[$cnt]; }
                switch ( $finder_type )
                {
                case 'most recent':
                    $additional_criteria['ORDER BY'][] = $this->primary_key_field.' DESC';
                    break;
                case 'first':
                    $additional_criteria['ORDER BY'][] = $this->primary_key_field.' ASC';
                    break;
                }

            return $this->find($finder_criteria, $additional_criteria);
        }
        else
        {
            throw new Exception("Method $method_name not defined");
                return null;
        }
            
    }
    private function __isset($name) {
        #debug echo 'testing isset '.$name; echo "<br>\r\n\r\n";

        #attributes / properties of the record
        if ($this->values && array_key_exists($name, $this->values)) 
        {
            return true;
        }
        else
        {
            /*
                echo $name.' = ';
            echo var_dump(isset($this->$name));
            echo var_dump(property_exists($this, $name));
            echo "<Br>\r\n";
             */
            return property_exists($this, $name);
            #return false;
        }

    }
    private function __get($name) {
        #debug echo 'getting '.$name; echo "<br>\r\n\r\n";
        #check for record_properties request
            if ($name == 'record') { return $this->values; }

        #check for changelog request
            if ($name == 'changelog' && $this->has_changelog)
            {
                if ($this->count == 0)
                {
                    throw new Exception("no changelog for empty ".$this->model_name);
                    return null;
                }
                $changelog_model_name = $this->model_name.'_changelog';
                $changelog = new $changelog_model_name();
                $changelog_find_method = 'find_most_recent_by_'.$this->model_name.'_id';
                $changelog->$changelog_find_method($this->values[$this->primary_key_field]);

                $this->changelog = $changelog;
                $changelog = null;
                return $this->changelog;
            }

        #if it's for reals then return it, and short circuit all the testing
            if (property_exists($this, $name)) { return $this->$name; }

        #attributes / properties of the record
            if (array_key_exists($name, $this->values)) 
            {
                #echo "\r\nreturning $name with value ";var_dump($this->values[$name]);echo "\r\n";
                return $this->values[$name]; 
            }

        #relationships magic
            if ($this->belongs_to($name)) {
                #if this object belongs to another one, it contains the foreign key
                #debug('finding by '.$name);
                if ($this->count == 0) { return false; }
                $ro = new $name;
                $fk = foreign_keyize($name);
                if (!$this->$fk) { return false; }
                $ro->find($this->$fk);
                return $ro;
            }

            if ($this->has_one($name) || $this->has_many($name))
            {
                #if this object has_one other obect, the other object contains the foreign key
                #echo 'finding by '.$name;
                if ($this->count == 0) { return false; }
                $ro = singularize($name); $ro = new $ro;
                $fk = foreign_keyize($this->model_name);
                $fkfunc = "find_by_$fk";

                #additional criteria checks
                    $additional_criteria = '';
                    if ($this->has_many($name)) { $additional_criteria = $this->has_many($name); }

                $ro->$fkfunc($this->values[$this->primary_key_field], $additional_criteria); // using areal world example: a category has_many products. this line translated means "return $product->find_by_category_id ($category->id )" */
                return $ro;
            }
            if ($this->has_many_through($name))
            {
                if ($this->count == 0) { return false; }

                $ro = singularize($name); $ro = new $ro;
                $link = singularize($this->has_many_through($name)); $link = new $link;
                $ro->find_by_sql('
                    SELECT '.$ro->dsn['database'].'.'.$ro->schema_table.'.* 
                    FROM '.$ro->dsn['database'].'.'.$ro->schema_table. ' INNER JOIN '.$link->dsn['database'].'.'.$link->schema_table.'
                    WHERE '.$link->dsn['database'].'.'.$link->schema_table.'.'.foreign_keyize($this->model_name).' = \''.$this->values[$this->primary_key_field].'\''
                );
                return $ro;
            }
            if ($this->through_model($name))
            {
                if ($this->count == 0) { return false; }
                $ro = singularize($name);
                $ro = new $ro;
                $fkfunc = "find_by_".foreign_keyize($this->model_name);
                $ro->$fkfunc($this->values[$this->primary_key_field]);
                return $ro;
            }

        throw new Exception("Property $name not defined");
        return null;
    }
    private function __set($name, $value) {
        #debug echo 'setting '.$name.' to <u>"'.$value.'"</u>'; echo "<br>\r\n";
        // check for primary_key_field
            if (property_exists($this, 'primary_key_field') && $name == $this->primary_key_field) {
                throw new Exception('Cannot modify value of primary key field');
                return false;
            }
        // check for properties, or values, of the record and set those in the values array
            if (isset($this->schema_definition) && (in_array($name, array_keys($this->values)))) {
                #debug if (1==2) { echo "setting dirty for $name to $value\r\n";}
                $this->dirty = true;
                $this->values[$name] = $value;
            }
        else {
            #var_dump($name);
            #raise an error
            #throw new Exception("$name is an invalid property");
            #debug echo "success setting $name to <u>'$value'</u><br>\r\n\r\n";
            $this->$name = $value;
        }

    }

    /*
     *    create a new record. analogous to ROR new method. A shortcut for clear_attributes
     */
    function create() {
        $this->clear_attributes();
    }
    function update() {
        return $this->save('update');
    }
    function save($save_type = "save") {
        if (!$this->dirty && $this->count == 0)
        { 
            $this->validation_errors = 'Cannot save: Record not dirty and no records found';
            return false;
        }
        #check for a save type of the default, save, but found record. maybe we want to update rather
        if ($save_type == 'save' && $this->count > 0) {
            $save_type = 'update';
        }

        #execute before validation actions
            if (method_exists($this, 'before_validation')) { $this->before_validation(); }
            if (method_exists($this, 'before_validation_on_'.$save_type)) { $this->{'before_validation_on_'.$save_type}(); }

        #validate object
            $allow_save = $this->is_valid();
            if (!$allow_save) { return false; }

        #execute before save/update actions
            if (method_exists($this, 'before_'.$save_type)) { $this->{'before_'.$save_type}(); }

        #error if schema definition does not exist
            if (!$this->schema_definition) { trigger_error("Schema definition does not exist for model ".get_class($this), E_USER_ERROR); }

        #build the field => value array, with empty values
            $collection = array_combine(array_keys($this->schema_definition), array_fill(0, sizeof($this->schema_definition), null));
            #debug(get_class($this));debug($save_type);print_r($collection);die();

        #remove the PK from attributes
            if (in_array($this->primary_key_field, array_keys($collection))) { unset ($collection[$this->primary_key_field]); }

        #populate the values in the fields array
            /* 2007-11-03 addslashes was once here, then in the past removed and now added here again for char escaping. pretty weird */
            foreach (array_keys($collection) as $attribute) { $collection[$attribute] = addslashes($this->values[$attribute]); }

        #sanitize things before saving

        #set the updated_on and/or created_on time
            $now = strftime(SQL_INSERT_DATE_TIME_FORMAT, time());

        if ($save_type == 'save')
        {
            #deal with created on, naturally only set on save
            if (in_array('created_on', array_keys($collection))) { $collection['created_on'] = $now; }
        }

        #deal with updated_on
            if (in_array('updated_on', array_keys($collection)) && !$this->preserve_updated_on) { $collection['updated_on'] = $now; }

        #deal with user_id
            if (in_array('user_id', array_keys($collection)))
            {
                if ($_SESSION[APP_NAME]['user_id'])
                {
                    $collection['user_id'] = $_SESSION[APP_NAME]['user_id'];
                }
            }

        if ($save_type == "save") { $record_id = $this->save_core($collection); }
        if ($save_type == "update") { $record_id = $this->update_core($collection); }

        #if the save failed, raise an error
           #todo... pass the exception back up to here, instead of doing an error check inside *_core 
        
        #save/update the changelog
            if ($this->has_changelog){ $this->changelog_entry($save_type); }

        #execute after save/update actions
            if (method_exists($this, 'after_'.$save_type)) { $this->{'after_'.$save_type}(); }

        return $record_id;
    }
    
    private function save_core($collection) {
        $fields = implode(',', array_keys($collection)); $values = "'".implode("','", array_values($collection))."'";
        $sql = 'INSERT INTO '.$this->dsn['database'].'.'.$this->schema_table." ($fields) VALUES ($values)";
        $this->last_sql_query = $sql;
        #debug ( $sql );die();
        $save = $this->db->query($sql);$this->error_check($save);
        
        #get the key of the new record
            $record_id = $this->db->lastInsertID($this->schema_table,$this->primary_key_field);
            $this->values[$this->primary_key_field] = $record_id;
        return $record_id;
    }
    private function update_core($collection) {
       foreach ($collection as $field => $value)
       {
           $collection[$field] = "'".$value."'";
       }

       $update_sql = implode_with_keys(',', $collection, "");
       $sql = 'UPDATE '.$this->dsn['database'].'.'.$this->schema_table." SET $update_sql WHERE ".$this->primary_key_field."=".$this->values[$this->primary_key_field];
       #debug($sql);
        $this->last_sql_query = $sql;
       $update = $this->db->query($sql);$this->error_check($update);
       return $this->values[$this->primary_key_field];
    }
    
    function save_multiple($collection) {
        /*
         * an example collection passed would be
         * customer_id => 25
         * product_id => Array(1, 2, 3, 4)
        */
        $new_records = array();
        foreach ($collection as $field => $value)
        {
            if (is_array($value)) #this takes the array portion of the collection
            {
                foreach ($value as $id)
                {
                    $new_records[] = array($field => $id);
                }
            }
        }
        /* by this point we will have a new records like so:
         * [0] => array('product_id' => 1)
         * [1] => array('product_id' => 2)
         * [2] => array('product_id' => 3)
         * [3] => array('product_id' => 4)
         */

        for ( $i=0; $i < sizeof($new_records); $i++ ) #for each of the non-array values of the collection...
        { 
            foreach ($collection as $field => $value)#put it in the sub-arrays of each new record
            {
                if (!is_array($value))
                {
                    $new_records[$i][$field] = $value;
                }
            }
            $this->update_attributes($new_records[$i]);
            $this->save();#save this new record
        }
        /* by this point we will have a new records like so:
         * [0] => array('customer_id', => 25, 'product_id' => 1)
         * [1] => array('customer_id', => 25, 'product_id' => 2)
         * [2] => array('customer_id', => 25, 'product_id' => 3)
         * [3] => array('customer_id', => 25, 'product_id' => 4)
         */
    }

    function delete_by_sql($sql) {
        $this->last_sql_query = $sql; 
        $this->results = $this->db->query($sql);
        if ( $this->results )
        {
            $this->count = 0;
            $this->clear_attributes();
            $this->error_check($this->results);
            return true;
        }
        return false;
    }
    function delete($criteria = null) {
        if ($criteria)
        {
            $sql_criteria = $this->criteria_to_sql($criteria);
        }
        elseif ($this->count > 0)
        {
            #i.e. delete just this record
            $sql_criteria = ' WHERE '.$this->dsn['database'].'.'.$this->schema_table.'.'.$this->primary_key_field.' = '.$this->values[$this->primary_key_field];
        }
        else
        {
            return false;
        }

        #mark deleted in changelog
            if ($this->has_changelog)
            {
               $to_delete = new $this->model_name;
                $changelog_criteria['SELECT']      = "*";
                $changelog_criteria['FROM']        = $this->dsn['database'].'.'.$this->schema_table;
                $changelog_criteria['WHERE']       = $sql_criteria;
               $to_delete->find_by_sql(SQL_implode($changelog_criteria));
               foreach($to_delete as $record)
               {
                   $this->changelog_entry('delete');
               }
            }

        $sql = "DELETE FROM ".$this->dsn['database'].'.'.$this->schema_table.' '.$sql_criteria;
        #debug($sql);
        return $this->delete_by_sql($sql);
    }

    /* changelog methods */
    /* inserts entries inthe the changelog on save or update.
     * marks the action as saved or updated if the changelog table has an action field
     */
    function changelog_entry($action) {
        #setup some values
            $record_id = $this->values[$this->primary_key_field];
            $collection = $this->values;
            $result = $this->changelog_highest_revision($record_id);
            $revision = $result[0]; $original_created_on = $result[1];

        #remove the id
            unset($collection[$this->primary_key_field]);
        #"model" id
            $collection[$this->model_name.'_id'] = $record_id;
        #revision
            $collection['revision'] = $revision + 1;
        #created_on
            if (in_array('created_on', array_keys($collection))) { $collection['created_on'] = $original_created_on; }
        #action
            $collection['action'] = $action;

        #instantiate the changelog object and save
            $changelog_model_name = $this->model_name.'_changelog';
            $changelog = new $changelog_model_name($collection);
            $changelog->save();
    }

    function changelog_highest_revision($record_id)
    {
        #get the highest revision id
        $sql = "SELECT MAX(revision) as rev_id, MAX(created_on) as created_on FROM ".$this->dsn['database'].'.'.$this->schema_table."_changelog WHERE ".$this->model_name.'_id'." = '".$record_id."'";
        #debug($sql);
        $rev_result = $this->db->query($sql);$this->error_check($rev_result);
        if ($rev_result)
        {
            $rev_result = $rev_result->fetchRow();

            $revision = $rev_result->rev_id;
            $created_on = $rev_result->created_on;
        }
        else {$revision = 0;}

        
        $return = array($revision, $created_on);
        return $return;
    }

    function write_value_changes(&$field, &$value)
    {
        switch ($field)
        {
            case 'password_md5':
                if ($value != '' && $this->dirty)
                {
                    if (method_exists($this,'encrypt_password')) #allows the model to have a custom password encryption method
                    {
                        $value = $this->encrypt_password($value);
                    }
                    else
                    {
                        $value = md5(PASSWORD_SALT.$value);
                    }
                    return true;
                }
                else
                {
                    return false;
                }
                break;
            case 'session_user_id': #here for backwards compatibility
                    $field = 'user_id';
                    $value = $_SESSION[APP_NAME]['user_id'];
                    return true;
                break;
            default:
                if (preg_match('/date/', $field) && $value != '' && $value != 0 && $value != null) #todo fix this hack
                {
                    $value = strftime(SQL_INSERT_DATE_FORMAT, strtotime($value));
                }
                if (!get_magic_quotes_gpc()) { $value = addslashes($value); }
                return true; 
        }
    }

    function criteria_to_sql($criteria) { #this method takes dynamic criteria and converts it to SQL 
        if (is_numeric($criteria)) {$sql_criteria = $this->dsn['database'].'.'.$this->schema_table.'.'.$this->primary_key_field.'='.$criteria;} #if passed a numeric value assume it's a Primary Key
        elseif (is_string($criteria)) {
            if (strtolower($criteria)== 'all') {
                $sql_criteria = '1=1';
            }
            else {
                $sql_criteria = $criteria;
            }
        }
        elseif (is_array($criteria)) {
            if (sizeof($criteria) > 0) {
                #I assume we are passing an array of ID's
                $sql_criteria = $this->dsn['database'].'.'.$this->schema_table.'.'.$this->primary_key_field.' IN (';
                foreach ($criteria as $id) {
                    $sql_criteria .= $id.',';
                } 
                $sql_criteria = substr($sql_criteria, 0, strlen($sql_criteria)-1);
                $sql_criteria .= ')';
            }
            else
            {
                $sql_criteria = '1=2';
            }
        }
        return $sql_criteria;
    }

    /* returns $this */
    function find_by_sql($sql) {
        $this->last_sql_query = $sql;
        $this->last_finder_sql_query = $sql;
        $this->results = $this->db->query($sql);
        if ( $this->results ) {
            $this->error_check($this->results);

            $this->count = $this->results->numRows();
            if ($this->count == 0) {
                $this->clear_attributes();
                #return false;
            }
            else {
                $this->offset = 0;
                $this->update_attributes();
                #return true;
            }
        }
        else {
            $this->count = 0;
            $this->clear_attributes();
            #return false;
        }
        return $this;
    }

    /* returns $this, by way of find_by_sql */
    function find($finder_criteria = null, $additional_sql_options = null) { //xxxfind
        if (!$finder_criteria && !$additional_sql_options) {
            throw new Exception('No criteria or additional options specified for finder');
            return;
        }

        $sql['SELECT']      = "*";
        $sql['FROM']        = $this->dsn['database'].'.'.$this->schema_table;
        $sql['WHERE']       = $this->criteria_to_sql($finder_criteria);

        if ($additional_sql_options) {
           
            $sql = SQL_merge($sql, $additional_sql_options);
        }

        $result = $this->find_by_sql(SQL_implode($sql));
        return $result;
    }

    /* returns $this */
    public function update_attributes($collection = null, $with_value_changes = false) {
        if ( !$collection ) { # if no row is passed then set the current row in results
            if ($this->results && !MDB2::isError($this->results)) {
                $collection = $this->results->fetchRow();
                $with_value_changes = false;
                $this->dirty = false;
                $this->new = false;
            }
        }
        else {
            #this object's data is dirty because it is coming from a collection
                $this->dirty = true;
                $with_value_changes = true;
        }

        if ( $collection ) {  # it's possible no collection was set with the DB lookup: checking again.
        #set row variables to properties
            foreach ($collection as $field => $value) {   
                if ($with_value_changes) {
                    #apply value changes to this field and value
                        $value_change_result = $this->write_value_changes($field, $value);
                        if ($value_change_result && $field) { $this->values[$field] = $value; }
                }
                else {
                    $this->values[$field] = $value;
                }
            }
        }
        else {
            $this->clear_attributes();
        }
        return $this;
    }

    public function clear_attributes() {
        if (property_exists($this, 'schema_definition') && is_array($this->schema_definition)) {
            foreach ($this->schema_definition as $attribute => $meta_data) {
                $this->values[$attribute] = null;
            }
        }
        $this->dirty = false;
    }

    /* todo: simply this method. It's a bit complicated to do something simple: then again, as_array now covers that */
    function as_collection($fields = null, $key_field = null, $compress_single_field = false) {
        if ($this->last_finder_sql_query == '') {
            throw new Exception('as_collection only works on a collection of records.');
            return false;
        }

        if (!$key_field) { $key_field = $this->primary_key_field; }
        if (!$fields) {$fields = $this->display_field;}
        if (!is_array($fields)) { $fields = array($fields); } #always make an array out of the fields
        $result = Array();

        $current_index = $this->results->offset; #get the current index of the MDB2 resultset, since we are going to be messing with it; I want to come back to the same place later
        $this->results->seek(0); #go to the beginning of the resultset
        while($record = $this->results->fetchRow())
        {
            $row = array();
             #create an array of all the requested fields
            foreach($fields as $field) {
                /* todo: test this functionality */
                if (substr($field, -2) == '()') {
                    $method = substr($field, 0, strlen($field)-2);
                    $row[$field] = $this->$method($record);
                }
                else {
                    $row[$field] = $record->$field;
                }
            }
            if ($compress_single_field && sizeof($fields == 1)) {
                $result[$record->$key_field] = $row[$fields[0]];
            }
            else {
                $result[$record->$key_field] = $row;
            }
        }
        $this->results->seek($current_index); #go back to the index stored earlier
        return $result;
    }
    
    /* as array is a simplified version of as_collection) */
    function as_array($field = null) {
        return $this->as_collection($field, $this->primary_key_field, true);
    }

    function as_select_options($selected = null, $field = null, $show_all_option = false) {
        $result = '';

        if ($show_all_option === 'all' || $show_all_option === true || $show_all_option == 'true') { $result .= '<option value="">-- Any --</option>'; }
        if ($show_all_option === 'none') { $result .= '<option value="">-- none --</option>'; }
        if ($show_all_option === 'select_one') { $result .= '<option value="">-- Select One --</option>'; }
        $options = $this->as_array($field);
        foreach ($options as $id => $value)
        {
            $result .= '<option value="'.$id.'"';
            if ($selected && $id == $selected)
            {
                $result .= ' selected="selected"';
            }
            $result .='>'.$value."</option>";
        }
        return $result;
    }

    /* relationship checking methods */
    function has_one($model_name) { 
        if (!property_exists($this, 'has_one')) {return false;}
        return in_array($model_name, split(',',$this->has_one));
    }

    function has_many($model_name) { 
        if (!property_exists($this, 'has_many')) {return false;}
        #first check if it is defined as an array
        if (is_array($this->has_many))
        {
            if (in_array($model_name, array_keys($this->has_many)))
            {
                return $this->has_many[$model_name];
            }
            else
            {
                return false;
            }
        }
        else
        {
            return in_array($model_name, split(',',$this->has_many));
        }
    }

    function belongs_to($model_name) { 
        if (!property_exists($this, 'belongs_to')) {return false;}
        return in_array($model_name, split(',',$this->belongs_to));
    }

    function through_model($model_name) #todo use a better name
    {
        if (!property_exists($this, 'has_many_through')) { return false; }
        if (!$this->has_many_through) { return false; }
        if (in_array($model_name, array_values($this->has_many_through)))
        {
            return true;
        }
        else
        {
            return false;
        }

    }
    function has_many_through($model_name) {

        if (!property_exists($this, 'has_many_through')) { return false; }
        if (!$this->has_many_through) { return false; }
        if (in_array($model_name, array_keys($this->has_many_through)))
        {
            return $this->has_many_through[$model_name];
        }
        else
        {
            return false;
        }
    
    }

    function requirements($field_name) {
        #todo flesh out this method
        #this method returns an english string explaining what the requirements for this field are... well, it will one day when I get there :)
        #debug("validation requirements for $field_name on ".get_class($this));
        if (property_exists($this, 'validates_presence_of')) { $required_fields = $this->validates_presence_of; } else { $required_fields = null; }
        
        if (is_array($required_fields) && in_array($field_name, $required_fields))
        {
            return '*';
        }
    }

    #todo flesh this out with other validation methods. Put in validations class?
    function is_valid() {
        $validation_result = array();
        if (property_exists($this, 'validates_presence_of'))
        {
            foreach ($this->validates_presence_of as $required_field)
            {
                $required_field = trim($required_field);
                if (!isset($this->$required_field) || !$this->$required_field || $this->$required_field == "")
                {
                    $human_field_name = $required_field;
                    if (substr($human_field_name, -3) == '_id') { $human_field_name = substr($human_field_name , 0, strlen($human_field_name)-3); } #todo fix this hack. need a new validation for associated records, natch.
                    $validation_result[$required_field]['message'] = humanize($human_field_name)." is empty";
                    $validation_result[$required_field]['error_type'] = 'error';
                }
            }
        }
        if (method_exists($this, 'validate'))
        {
            $custom_validation_result = $this->validate();
            if ($custom_validation_result)
            {
                $validation_result = array_merge($validation_result, $custom_validation_result);
            }
        }
        if (isset($validation_result) && sizeof($validation_result) > 0)
        {
            $this->validation_result = $validation_result;
            $this->validation_errors = 'Error:';
            foreach ($this->validation_result as $single_result)
            {
                $this->validation_errors .= '<br />'.$single_result['message'];
            }
            return false;
        }

        return true;
    }
    

    function sum() {
        if (!property_exists($this, 'sum_field')) { return false; }
        if ($this->count == 0) { return 0; }

        $current_index = $this->key(); #get the current index of the MDB2 resultset, since we are going to be messing with it; I want to come back to the same place later
        $this->rewind(); #go to the beginning of the resultset

        $sum = 0;
        foreach ($this as $record)
        {
            $sum += $this->{$this->sum_field};
        }

        $this->seek($current_index); #go back to the index stored earlier

        return $sum;
    }
    function display_name() {
        if ($this->count == 0) { return false; }
        return $this->values[$this->display_field];
    }

    function error_check($result, $die_on_error = true) {
        if (PEAR::isError($result) || MDB2::isError($result)) {
            if ($die_on_error)
            {
                #die('<pre>'.$result->getMessage().' - '.$result->getUserinfo()).'</pre>';
                throw new Exception($result->getMessage().' - '.$result->getUserinfo());
                return null;
            }
            else
            {
                return $result->code;
            }
        }
    }
    
    #methods required for iterator implementation
    function current() {
        return $this;
    }
    function key() {
        if ($this->valid())
        {
            return $this->offset;
        }
    }
    function seek($index) {
        if ($this->valid())
        {
            $this->results->seek($index);
            $this->offset = $index;

            $this->update_attributes();

            return true;
        }
        return false;
    }
    function valid() {
        if ($this->count > 0 && !MDB2::isError($this->results) && $this->offset < $this->count) { return true; } else { return false; }

    }
    function rewind() {
        if ($this->count > 0 && !MDB2::isError($this->results)) #not using valid() because valid checks offset. when @ end it could never rewind then!
        {
            $this->offset = 0;
            $this->seek(0);
        }
    }
    function next() {
        $this->seek($this->offset+1);
    }

#todo put this somewhere else!
#this is used in functions.php
static $sql_phrases = array(
    'SELECT'    => ', ',
    'FROM'      => ', ',
    'WHERE'     => ' ',
    'GROUP BY'  => ', ',
    'ORDER BY'  => ','
);
}
/* AR HELPERS starts here */
function compare_records($record1, $record2, $include_boilerplate = false) {
    if (!is_array($record1)) { $record1 = $record1->values; }
    if (!is_array($record2)) { $record2 = $record2->values; }
    $changed_attributes = null;
    #todo check that these are the same type of object
    foreach($record1 as $attribute => $attribute_value)
    {
        if ($record1[$attribute] != $record2[$attribute])
        {
            switch ($attribute)
            {
                case 'id':
                case 'updated_on':
                case 'created_on':
                case 'revision':
                case 'user_id':
                    if (!$include_boilerplate) {break;}
                default:
                    $changed_attributes[] = $attribute;
            }
        }
    }
    return $changed_attributes;

}
#docs - todo make this use a proper php documenting standard
#
#more callbacks todo:
#before_delete (great for cancelling delete and marking as "deleted")
#after_delete
?>
