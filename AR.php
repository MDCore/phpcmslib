<?php
/**
 * This file implements the ActiveRecord pattern for Pedantic_Lib
 *
 * @category  PHP
 * @package   Pedantic_Lib
 * @author    Gavin van Lelyveld <gavin@pedantic.co.za>
 * @copyright 2007 Gavin van Lelyveld
 * @license   proprietary http://pedantic.co.za
 * @link      http://pedantic.co.za
 **/
class AR implements SeekableIterator
{
    public $dirty = false;
    public $new = true;
    public $count = 0;
    public $validation_result = null; public $validation_errors = null;
    public $preserve_updated_on = false;
    public $db = null, $schema_definition;
    public $dsn;

    /* AR related fields - now that __get forbids getting random properties,
     * these can be here as settable but not gettable */
    /*
        public $model_name, $primary_key_field, $schema_table, $display_field, ;
     */
    public $has_one, $has_many, $has_many_through, $belongs_to;

    public $last_sql_query, $last_finder_sql_query;
    private $offset = 0;
    private $results;
    private $values = array();


    /**
     * connects this object to the database
     *
     * @param array $dsn default is to use the current environments DSN.
     *                   Otherwise use the specified DSN to connect this object
     *
     * @return void
     */
    function connect_to_db($dsn = null)
    {
        if (!$dsn && isset(App::$env)) {
            $dsn = App::$env->dsn;
        }
        if ($dsn) {
            //debug("connecting to ".$dsn['database']);
            $this->dsn = $dsn;

            //$this->db =& MDB2::singleton($dsn);
            $this->db =& MDB2::factory($dsn);
            //print_r($this->db);
            $this->error_check($this->db);

            if ($this->acts_as_nested_set) {
                $this->nested_set = null;
                $this->nested_set =& DB_NestedSet::factory('MDB2', $this->dsn, $this->nested_set_params);
                $this->error_check($this->nested_set);
                $this->nested_set->setAttr(array(
                    'node_table' => $this->nested_set_node_table,
                    'lock_table' => $this->nested_set_lock_table
                ));
            }
        }

        if ($this->db) {
            $this->db->setFetchMode(MDB2_FETCHMODE_OBJECT);
        }
    }

    /**
     * pulls in the schema definition and creates the attributes
     * in the object, setting them to null
     *
     * @return boolean
     */
    function setup_attributes()
    {
        if (!isset(App::$schema_definition[$this->model_name])) {
            return false;
        }

        $this->schema_definition = App::$schema_definition[$this->model_name];
        $this->clear_attributes();
    }

    /**
     * destructor
     *
     * @return void
     */
    function __destruct()
    {
        unset($this->db);
        unset($this->values);
    }

    /** constructor
     *
     * @param array   $collection         a collection of values in the format [field name] => value with which to initialize this record
     * @param boolean $with_value_changes default 'true'. This allows bypassing the value_changes (??link??) call
     *
     * @return void
     */
    function __construct($collection = null, $with_value_changes = true)
    {
        //debug echo "<b>before setting pk</b><br>\r\n";
        /* set the default primary key field */
        if (!property_exists($this, 'primary_key_field')) {
            $this->primary_key_field = 'id';
        }
        //debug echo "<b>after setting pk</b><br>\r\n\r\n";

        /* get the model name */
        $this->model_name = get_class($this);
        //debug echo "<b>after setting_model_name</b><br>\r\n\r\n";

        /* pull in the schema definition, and set the record attributes to null */
            $this->setup_attributes();
        //debug echo('<b>after setup_attribs</b><br>');

        /* set the primary table, checking first if this model_name is a changelog model */
        if (!property_exists($this, 'schema_table')) {
            /* is this model a changelog model? */
            $changelog_pos = strpos($this->model_name, '_changelog');
            if ($changelog_pos > 0) {
                /*
                 * check if the 'parent' model has a schema_table and use it
                 * instead of inferring the table name from the parent model name
                 */
                $parent_model_name = substr($this->model_name, 0, $changelog_pos);
                $parent_model_name = new $parent_model_name;
                if (isset($parent_model_name->schema_table)) {
                    /* determine the changelog table name based on the parent model schema table name */
                    $this->schema_table = $parent_model_name->schema_table.'_changelog';
                } else {
                    /* determine the changelog table name based on the parent model name */
                    $this->schema_table = tableize(pluralize(substr($this->model_name, 0, $changelog_pos))).'_changelog';
                }
                unset($parent_model_name);
            } else {
                /* not a changelog at all. Just set the schema table name based on the model name */
                $this->schema_table = tableize(pluralize($this->model_name));
            }
        }
        //debug echo('<b>after setting primary table etc</b><br>');

        /* does this model have a changelog? */
        if (property_exists($this->model_name, 'changelog')) {
            $this->has_changelog = true; unset($this->changelog);
        } else {
            $this->has_changelog = false;
        }

        /*set the display field. acts_as_nested_set needs it */
        if (!property_exists($this, 'display_field')) {
            if (isset($this->schema_definition['title'])) {
                $this->display_field = 'title';
            } elseif (isset($this->schema_definition['name'])) {
                $this->display_field = 'name';
            } else {
                $this->display_field = 'id';
            }
        }

        //debug echo('<b>after setting display_field</b><br>');

        /* set the acts_as_nested_set property. connect_to_db() needs it */
        if (property_exists($this, 'acts_as_nested_set')) {
            $this->acts_as_nested_set = true;

            /*
             * an array of field names that DB_NestedSet expects.The format is quite
             * strange: OUR name for the field is on the left while the name
             * that DB_NestedSet expects is the one on the right
             */
            $this->nested_set_params = array(
                'id' => 'id',
                'ns_root_id' => 'rootid',
                'ns_parent_id' => 'parent',
                'ns_left_id' => 'l',
                'ns_right_id' => 'r',
                'ns_node_order' => 'norder',
                'ns_level' => 'level',
                $this->display_field => 'name'
            );
            if (isset($this->schema_definition)) { /* sometimes the schema definition is not set at this point but that's ok
                                                    * because that seems to be during migration, where the schema def is going to
                                                    * be set for this model soon
                                                    */
            /* add the other fields in the schema to the nested_set_params */
                foreach (array_keys($this->schema_definition) as $schema_field) {
                    if (!array_key_exists($schema_field, $this->nested_set_params)) {
                        $this->nested_set_params[$schema_field] = $schema_field;
                    }
                }
            }

            /* set the node table and the lock table */
            $this->nested_set_node_table = $this->schema_table; // I don't see any reason why the node table is not the schema table
            if (!property_exists($this, 'nested_set_lock_table')) {
                $this->nested_set_lock_table = $this->schema_table.'_locks';
            }

        } else {
            $this->acts_as_nested_set = false;
        }

        /* nested_set and changelog are mutually exclusive, because of the types of
         * changes that a nested set requires i.e. changing the tree position of a
         * single record possibly means changing a large part of the whole tree.
         * This will flood the changelog. I'm requiring them to be mutually
         * exclusive until we can solve this problem.
         */
        if ($this->acts_as_nested_set && $this->has_changelog) {
            trigger_error("Properties acts_as_nested_set and has_changelog are mutually exclusive", E_USER_ERROR); die();
        }

        /*
         * split the validations
         * todo. maybe use getobjectvars?
         * todo add all validations
         */
        $validations = array('validates_presence_of');
        foreach ($validations as $validation) {
            if (property_exists($this, $validation)) {
                $this->$validation = explode(',', $this->$validation);
            }
        }
        //debug echo('<b>after validation setup</b><br>');

        /* update the attributes if the object is created with a collection */
        if ($collection) {
            $this->update_attributes($collection, $with_value_changes);
        }

        /* connect this model to the database */
        if (!$this->db) {
            $this->connect_to_db();
        }
    }

    /**
     * handles dynamic finders, also known as magic methods.
     * an example: customer->find_by_firstname_and_lastname('john', 'smith');
     * that method does not exist so __call breaks down the method call and converts it to parameters for find() (??link??)
     *
     * @param string $method_name The name of the method that was called
     * @param array  $params      The collection of method parameters that were part of the method call
     *
     * @return void
     */
    private function __call($method_name, $params)
    {
        /* nested set function checks */
        if ($this->acts_as_nested_set) {

            if ($method_name == 'children') {
                if ($this->count == 0) { /* we have to do this here, for each method because doing it once for all the methods will return null even if we are just doing a basic find() */
                    return null;
                }
                /* getChildren() returns a multi-dimensional array of records. */
                $child_ids = $this->nested_set->getChildren($this->values[$this->primary_key_field], true); // parameters: id_field, keep_as_array?
                /* get just the record_id's so that we can find() those records (rather than working with an array) */
                $children = new $this->model_name;
                if (is_array($child_ids) && sizeof($child_ids) > 0 ) {
                    $child_ids = array_keys($child_ids);
                    $children->find($child_ids);
                }
                return $children;
            }
            if ($method_name == 'sub_branch') {
                if ($this->count == 0) { /* we have to do this here, for each method because doing it once for all the methods will return null even if we are just doing a basic find() */
                    return null;
                }
                /* getSubBranch() returns a multi-dimensional array of records. */
                $branch_ids = $this->nested_set->getSubBranch($this->values[$this->primary_key_field], true); // parameters: id_field, keep_as_array?
                /* get just the record_id's so that we can find() those records (rather than working with an array */
                $branch = new $this->model_name;
                if (is_array($branch_ids) && sizeof($branch_ids) > 0 ) {
                    $branch_ids = array_keys($branch_ids);
                    $branch->find($branch_ids);
                }
                return $branch;
            }
        }
        //overload finders
        $finder_criteria_pos = null; $finder_type = null;
        if (substr($method_name, 0, 8) == 'find_by_') {
            $finder_criteria_pos = 8;
        }
        if (substr($method_name, 0, 20) == 'find_most_recent_by_') {
            $finder_criteria_pos = 20; $finder_type = 'most recent';
        }
        if (substr($method_name, 0, 11) == 'find_first_by_') {
            $finder_criteria_pos = 11; $finder_type = 'first';
        }

        if ($finder_criteria_pos) {
            $find_by = substr($method_name, $finder_criteria_pos);
            $find_by = explode('_and_', $find_by);
            $finder_criteria = array();

            $finder_where = '';
            $cnt = 0;
            foreach ($find_by as $find_by_field) {
                $finder_where .= $this->dsn['database'].'.'.$this->schema_table.'.'.$find_by_field." = '".$params[$cnt]."' AND ";
                $cnt++;
            }
            if ($cnt > 0 ) {
                // strip the last AND from the finder criteria string
                $finder_where = '('.substr($finder_where, 0, strlen($finder_where)-5).')';
            }
            $finder_criteria['WHERE'][] = $finder_where;

            //special finders
            if (is_array($params[$cnt])) {
                $finder_criteria = SQL_merge($finder_criteria, $params[$cnt]);
            }
            switch ($finder_type) {
            case 'most recent':
                $finder_criteria['ORDER BY'][] = $this->primary_key_field.' DESC';
                break;
            case 'first':
                $finder_criteria['ORDER BY'][] = $this->primary_key_field.' ASC';
                break;
            }

            return $this->find(null, $finder_criteria);
        } else {
            throw new Exception("Method $method_name not defined");
                return null;
        }

    }

    /**
     * checks if an allowed variable is set
     *
     * @param string $name the name of the variable to check
     *
     * @return boolean
     */
    private function __isset($name)
    {
        //debug echo 'testing isset '.$name; echo "<br>\r\n\r\n";

        //attributes / properties of the record
        if ($this->values && array_key_exists($name, $this->values)) {
            return true;
        } else {
            /*
                echo $name.' = ';
            echo var_dump(isset($this->$name));
            echo var_dump(property_exists($this, $name));
            echo "<Br>\r\n";
             */
            return property_exists($this, $name);
            //return false;
        }

    }

    /**
     * deals with magic properties like relationships and also with
     * returning record properties
     *
     * @param string $name variable name
     *
     * @return variant returns an AR object for relationships and changelog
     *                 returns a property for record properties
     */
    private function __get($name)
    {
        //debug echo 'getting '.$name; echo "<br>\r\n\r\n";
        //check for record_properties request
        if ($name == 'record') {
            return $this->values;
        }
        /* check for special value session_user_id and return user_id. Here for backwards compatibility with old apps */
        if ($name == 'session_user_id' && array_key_exists('user_id', $this->values)) {
            return $this->values['user_id'];
        }


        /* check for a changelog request */
        /* todo: re-use changelog, or set the model names etc in the __construct */
        if ($name == 'changelog' && $this->has_changelog) {
            if ($this->count == 0) {
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

        /*if it's for reals then return it, and short circuit all the checks */
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        /* attributes / properties / values of the record */
        if (array_key_exists($name, $this->values)) {
            //echo "\r\nreturning $name with value ";var_dump($this->values[$name]);echo "\r\n";
            return $this->values[$name];
        }

        /* relationships magic */
        if ($this->belongs_to($name)) {
            /* if this object belongs to another one, this object contains the foreign key */
            //debug('finding by '.$name);
            if ($this->count == 0) {
                return false;
            }
            $ro = new $name;
            $fk = foreign_keyize($name);
            if (!$this->$fk) {
                return false;
            }
            $ro->find($this->$fk);
            return $ro;
        }

        if ($this->has_one($name) || $this->has_many($name)) {
            /*
             * if this object has_one other obect, the other object
             * contains the foreign key
             */
            //echo 'finding by '.$name;
            if ($this->count == 0) {
                return false;
            }
            $ro = singularize($name); $ro = new $ro;
            $fk = foreign_keyize($this->model_name);
            $fkfunc = "find_by_$fk";

            /*additional criteria checks */
            $additional_criteria = '';
            if ($this->has_many($name)) {
                $additional_criteria = $this->has_many($name);
            }

            $ro->$fkfunc($this->values[$this->primary_key_field], $additional_criteria); // using areal world example: a category has_many products. this line translated means "return $product->find_by_category_id ($category->id )" */
            return $ro;
        }
        if ($this->has_many_through($name)) {
            if ($this->count == 0) {
                return false;
            }

            $ro = singularize($name); $ro = new $ro;
            $link = singularize($this->has_many_through($name)); $link = new $link;
            $sql_ro = '
                SELECT '.$ro->dsn['database'].'.'.$ro->schema_table.'.*
                FROM '.$ro->dsn['database'].'.'.$ro->schema_table. ' INNER JOIN '.$link->dsn['database'].'.'.$link->schema_table.'
                ON '.$ro->dsn['database'].'.'.$link->schema_table.'.'.foreign_keyize(singularize($ro->schema_table)).'
                     = '.$ro->dsn['database'].'.'.$ro->schema_table.'.'.$ro->primary_key_field.'
                WHERE '.$link->dsn['database'].'.'.$link->schema_table.'.'.foreign_keyize($this->model_name).' = \''.$this->values[$this->primary_key_field].'\'';
            $ro->find_by_sql($sql_ro);
            $this->last_sql_query = $sql_ro;
            return $ro;
        }
        if ($this->through_model($name)) {
            if ($this->count == 0) {
                return false;
            }
            $ro = singularize($name);
            $ro = new $ro;
            $fkfunc = "find_by_".foreign_keyize($this->model_name);
            $ro->$fkfunc($this->values[$this->primary_key_field]);
            return $ro;
        }

        throw new Exception("Property $name not defined");
        return null;
    }

    /**
     * sets an object property named $name to $value.
     * It will refuse to set the primary key field.
     *
     * @param string $name  the name of the property to set
     * @param string $value the value to set the property to
     *
     * @return void
     */
    private function __set($name, $value)
    {
        //debug echo 'setting '.$name.' to <u>"'.$value.'"</u>'; echo "<br>\r\n";
        // check for primary_key_field
        if (property_exists($this, 'primary_key_field') && $name == $this->primary_key_field) {
            throw new Exception('Cannot modify value of primary key field');
            return false;
        }
        // check for nested set parent_id setting, rename it to ns_parent_id
        if (property_exists($this, 'acts_as_nested_set') && 'parent_id' == $name) {
            $this->values['ns_parent_id'] = $value;
            return true;
        }
        // check for properties, or values, of the record and set those in the values array
        if (isset($this->schema_definition) && (in_array($name, array_keys($this->values)))) {
            //debug if (1==2) { echo "setting dirty for $name to $value\r\n";}
            $this->dirty = true;
            $this->values[$name] = $value;
        } else {
            //debug echo "success setting $name to <u>'$value'</u><br>\r\n\r\n";
            $this->$name = $value;
        }

    }

    /**
     * creates a new record; analogous to ROR new method. A shortcut for clear_attributes
     *
     * @return AR
     */
    function create()
    {
        $this->clear_attributes();
        return $this;
    }

    /**
     * works on an existing record in the database and updates it.
     *
     * @return integer or boolean
     */
    function update()
    {
        return $this->save('update');
    }

    /**
     * works on a brand new object, without a record in the database
     * and saves it to the database. In actuality this method handles
     * both save and update.
     *
     * @param string $save_type default is "save". whether or not to process this 'save' as a save or an update
     *
     * @return integer or boolean
     */
    function save($save_type = "save")
    {
        if (!$this->dirty && $this->count == 0) {
            $this->validation_errors = 'Cannot save: Record not dirty and no records found';
            return false;
        }
        //check for a save type of the default, save, but found record. maybe we want to update rather
        if ($save_type == 'save' && $this->count > 0) {
            $save_type = 'update';
        }

        //execute before validation actions
        if (method_exists($this, 'before_validation')) {
            $this->before_validation();
        }
        if (method_exists($this, 'before_validation_on_'.$save_type)) {
            $this->{'before_validation_on_'.$save_type}();
        }

        //validate object
        $allow_save = $this->is_valid();
        if (!$allow_save) {
            return false;
        }

        //execute before save/update actions
        if (method_exists($this, 'before_'.$save_type)) {
            $this->{'before_'.$save_type}();
        }

        //error if schema definition does not exist
        if (!$this->schema_definition) {
            trigger_error("Schema definition does not exist for model ".get_class($this), E_USER_ERROR);
        }

        //build the field => value array, with empty values
        $collection = array_combine(array_keys($this->schema_definition), array_fill(0, sizeof($this->schema_definition), null));
        //debug(get_class($this));debug($save_type);print_r($collection);die();

        //remove the PK from attributes
        if (in_array($this->primary_key_field, array_keys($collection))) {
            unset ($collection[$this->primary_key_field]);
        }

        //populate the values in the fields array
        /* 2007-11-03 addslashes was once here, then in the past removed and now added here again for char escaping. pretty weird */
        foreach (array_keys($collection) as $attribute) {
            $collection[$attribute] = addslashes($this->values[$attribute]);
        }

        //sanitize things before saving

        //set the updated_on and/or created_on time
            $now = strftime(SQL_INSERT_DATE_TIME_FORMAT, time());

        if ($save_type == 'save') {
            //deal with created on, naturally only set on save
            if (in_array('created_on', array_keys($collection))) {
                $collection['created_on'] = $now;
            }
        }

        /* deal with updated_on */
        if (in_array('updated_on', array_keys($collection)) && !$this->preserve_updated_on) {
            $collection['updated_on'] = $now;
        }

        if ($save_type == "save") {
            $record_id = $this->save_core($collection);
        }
        if ($save_type == "update") {
            $record_id = $this->update_core($collection);
        }

        /* if the save failed, raise an error */
           //todo... pass the exception back up to here, instead of doing an error check inside *_core

        /* save/update the changelog */
        if ($this->has_changelog) {
            $this->changelog_entry($save_type);
        }

        /*execute after save/update actions */
        if (method_exists($this, 'after_'.$save_type)) {
            $this->{'after_'.$save_type}();
        }

        return $record_id;
    }

    /**
     * does the actual saving to the database by converting the collection to SQL
     *
     * @param array $collection An array of field names (matching the schema) and values to save
     *
     * @return integer
     */
    private function save_core($collection)
    {
        /* is this model a nested_set ? Use the nested_set connection to do the initial save */
        if ($this->acts_as_nested_set) {

            /* unset the nested_set fields */
            foreach (array('ns_left_id', 'ns_right_id', 'ns_root_id', 'ns_node_order', 'ns_level') as $ns_field) {
                unset($collection[$ns_field]);
            }

            if (!isset($collection['ns_parent_id']) || $collection['ns_parent_id'] == '') {

                unset($collection['ns_parent_id']);

                /* create a root node
                 * parameters:
                 *      collection,
                 *      no target node_id,
                 *      don't reinit the tree (true seems to delete the entire tree and start again
                 *          ensuring that there is only one root node. most likely a bad idea.)
                 */
                $record_id = $this->nested_set->createRootNode($collection, false, false);

            } else {

            //print_r($collection);die();
                /* create a child node */
                $record_id = $this->nested_set->createSubNode($collection['ns_parent_id'], $collection);
            }
        } else {

            /* build the sql query */
            $fields = implode(',', array_keys($collection)); $values = "'".implode("','", array_values($collection))."'";
            $sql = 'INSERT INTO '.$this->dsn['database'].'.'.$this->schema_table." ($fields) VALUES ($values)";

            $this->last_sql_query = $sql;
            //debug ( $sql );die();

            $save = $this->db->query($sql); $this->error_check($save);

            /* get the key of the new record */
            $record_id = $this->db->lastInsertID($this->schema_table, $this->primary_key_field);
            $this->values[$this->primary_key_field] = $record_id;
        }

        return $record_id;
    }

    /**
     * does the actual updating to the database by converting the collection to SQL
     *
     * @param array $collection An array of field names (matching the schema) and values to update
     *
     * @return integer
     */
    private function update_core($collection)
    {
        $record_id = $this->values[$this->primary_key_field];

        /* is this model a nested_set ? Use the nested_set connection to handle updates */
        if ($this->acts_as_nested_set) {
            $this->nested_set->updateNode($record_id, $collection);
        } else {
            foreach ($collection as $field => $value) {
                $collection[$field] = "'".$value."'";
            }

            /* build the sql query */
            $update_sql = implode_with_keys(',', $collection, "");
            $sql = 'UPDATE '.$this->dsn['database'].'.'.$this->schema_table." SET $update_sql WHERE ".$this->primary_key_field."=".$this->values[$this->primary_key_field];

            $this->last_sql_query = $sql;

            //debug($sql);
            $update = $this->db->query($sql); $this->error_check($update);
        }

        return $record_id;
    }

    /**
     * saves multiple records to the database (warning, old cold)
     * an example collection passed would be
     * customer_id => 25
     * product_id => Array(1, 2, 3, 4)
     *
     * @param array $collection a collection of records to save
     *
     * @return void
    */
    function save_multiple($collection)
    {
        $new_records = array();
        foreach ($collection as $field => $value) {
            if (is_array($value)) { //this takes the array portion of the collection
                foreach ($value as $id) {
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

        for ( $i=0; $i < sizeof($new_records); $i++ ) { //for each of the non-array values of the collection...
            foreach ($collection as $field => $value) {//put it in the sub-arrays of each new record
                if (!is_array($value)) {
                    $new_records[$i][$field] = $value;
                }
            }
            $this->update_attributes($new_records[$i]);
            $this->save();//save this new record
        }
        /* by this point we will have a new records like so:
         * [0] => array('customer_id', => 25, 'product_id' => 1)
         * [1] => array('customer_id', => 25, 'product_id' => 2)
         * [2] => array('customer_id', => 25, 'product_id' => 3)
         * [3] => array('customer_id', => 25, 'product_id' => 4)
         */
    }

    function delete_by_sql($sql)
    {
        $this->last_sql_query = $sql;
        $this->results = $this->db->query($sql);
        if ($this->results) {
            $this->count = 0;
            $this->clear_attributes();
            $this->error_check($this->results);
            return true;
        }
        return false;
    }

    function delete($criteria = null)
    {
        if ($criteria) {
            $sql_criteria = $this->criteria_to_sql($criteria);
        } elseif ($this->count > 0) {
            //i.e. delete just this record
            $sql_criteria = ' WHERE '.$this->dsn['database'].'.'.$this->schema_table.'.'.$this->primary_key_field.' = '.$this->values[$this->primary_key_field];
        } else {
            return false;
        }

        //mark deleted in changelog
        if ($this->has_changelog) {
            $to_delete = new $this->model_name;
            $changelog_criteria['SELECT']      = $this->dsn['database'].'.'.$this->schema_table.'.*';
            $changelog_criteria['FROM']        = $this->dsn['database'].'.'.$this->schema_table;
            $changelog_criteria['WHERE']       = $sql_criteria;
            $to_delete->find_by_sql($changelog_criteria);
            foreach ($to_delete as $record) {
                $this->changelog_entry('delete');
            }
        }

        $sql = "DELETE FROM ".$this->dsn['database'].'.'.$this->schema_table.' '.$sql_criteria;
        //debug($sql);
        return $this->delete_by_sql($sql);
    }

    /**
     * inserts entries into the the changelog on save or update.
     * marks the action as saved or updated if the changelog table has an action field
     *
     * @param string $action a change action on the record. 'update', 'save' or 'delete'
     *
     * @return void
     */
    function changelog_entry($action)
    {
        //setup some values
            $record_id = $this->values[$this->primary_key_field];
            $collection = $this->values;
            $result = $this->changelog_highest_revision($record_id);
            $revision = $result[0]; $original_created_on = $result[1];

        //remove the id
            unset($collection[$this->primary_key_field]);
        //"model" id
            $collection[$this->model_name.'_id'] = $record_id;
        //revision
            $collection['revision'] = $revision + 1;
        //created_on
        if (in_array('created_on', array_keys($collection))) {
            $collection['created_on'] = $original_created_on;
        }
        //action
            $collection['action'] = $action;

        //instantiate the changelog object and save
            $changelog_model_name = $this->model_name.'_changelog';
            $changelog = new $changelog_model_name($collection);
            $changelog->save();
    }

    /**
     * gets the highest revision of a specific record
     *
     * @param integer $record_id id of the record for which we are finding the highest revision
     *
     * @return array  an array consisting of (the highest revision number, the created date)
     */
    function changelog_highest_revision($record_id)
    {
        //get the highest revision id
        $sql = "SELECT MAX(revision) as rev_id, MAX(created_on) as created_on FROM ".$this->dsn['database'].'.'.$this->schema_table."_changelog WHERE ".$this->model_name.'_id'." = '".$record_id."'";
        //debug($sql);
        $rev_result = $this->db->query($sql);$this->error_check($rev_result);
        if ($rev_result) {
            $rev_result = $rev_result->fetchRow();

            $revision = $rev_result->rev_id;
            $created_on = $rev_result->created_on;
        } else {
            $revision = 0;
        }


        $return = array($revision, $created_on);
        return $return;
    }

    /**
     * used to modify values in the save/update collection
     * e.g. password_md5 is changed to the md5 version of the password
     *
     * @param string &$field field name to test
     * @param string &$value the current value of that field
     *
     * @return boolean
     */
    function write_value_changes(&$field, &$value)
    {
        switch ($field) {
        case 'password_md5':
            if ($value != '' && $this->dirty) {
                if (method_exists($this, 'encrypt_password')) { /*allows the model to have a custom password encryption method */
                    $value = $this->encrypt_password($value);
                } else {
                    $value = md5(PASSWORD_SALT.$value);
                }
                return true;
            } else {
                return false;
            }
            break;
        case 'session_user_id': //here for backwards compatibility
                $field = 'user_id';
                $value = $_SESSION[APP_NAME]['user_id'];
                return true;
            break;
        default:
            /*todo fix this hack */
            if (preg_match('/date/', $field) && $value != '' && $value != 0 && $value != null) {
                $value = strftime(SQL_INSERT_DATE_FORMAT, strtotime($value));
            }
            /*
             * removed because I"m handling the slashes more intelligently
            * if (!get_magic_quotes_gpc()) {
                $value = addslashes($value);
            }*/
            return true;
        }
    }

    /**
     * Takes criteria for modifying the WHERE clause of an SQL statement
     * and converts them to SQL.
     * This method is used by find() and delete()
     *
     * @param variant $criteria if criteria is numeric it is assumed to be a primary key ....etcetc
     *
     * @return string the criteria as SQL
     */
    function criteria_to_sql($criteria)
    {
        /*if passed a numeric value we assume it's a Primary Key */
        if (is_numeric($criteria)) {
            $sql_criteria =
                $this->dsn['database'].'.'.
                $this->schema_table.'.'.
                $this->primary_key_field.'='.
                $criteria;
        } elseif (is_string($criteria)) {
            /* a string can be either 'ALL' or pure SQL */
            if (strtolower($criteria)== 'all') {
                $sql_criteria = '1=1';
            } else {
                $sql_criteria = $criteria;
            }
        } elseif (is_array($criteria)) {
            if (sizeof($criteria) > 0) {
                /*I assume we are beign passed an array of ID's */
                $sql_criteria = $this->dsn['database'].'.'.$this->schema_table.'.'.$this->primary_key_field.' IN (';
                foreach ($criteria as $id) {
                    $sql_criteria .= $id.',';
                }
                $sql_criteria = substr($sql_criteria, 0, strlen($sql_criteria)-1);
                $sql_criteria .= ')';
            } else {
                /* an empty array ? what a strange thing to pass. No records for you! */
                $sql_criteria = '1=2';
            }
        }
        return $sql_criteria;
    }

    /**
     * do an sql query
     *
     * @param mixed $sql an sql query or an SQL collection
     *
     * @return AR
     */
    function find_by_sql($sql)
    {

        if (is_array($sql)) {
            $sql = SQL_implode($sql);
        }

        $this->last_sql_query = $sql;
        $this->last_finder_sql_query = $sql;
        $this->results = $this->db->query($sql);
        if ( $this->results ) {
            $this->error_check($this->results);

            $this->count = $this->results->numRows();
            if ($this->count == 0) {
                $this->clear_attributes();
                //return false;
            } else {
                $this->offset = 0;
                $this->update_attributes();
                //return true;
            }
        } else {
            $this->count = 0;
            $this->clear_attributes();
            //return false;
        }
        return $this;
    }

    /**
     * builds a SQL statement based on dynamic criteria
     *
     * @param string $finder_criteria        criteria that modify the WHERE clause of the SQL
     * @param array  $additional_sql_options this is merged with the SQL array, thus allowing additional SELECT, FROM etc criteria to be specified by a finder
     *
     * @return AR
     */
    function find($finder_criteria = null, $additional_sql_options = null)
    {
        /* returns $this, by way of find_by_sql */
        //xxxfind
        if (!$finder_criteria && !$additional_sql_options) {
            throw new Exception('No criteria or additional options specified for finder');
            return;
        }

        $sql['SELECT']      = $this->dsn['database'].'.'.$this->schema_table.'.*';
        $sql['FROM']        = $this->dsn['database'].'.'.$this->schema_table;

        if($finder_criteria){
            $sql['WHERE']       = $this->criteria_to_sql($finder_criteria);
        }

        if ($additional_sql_options) {
            $sql = SQL_merge($sql, $additional_sql_options);
        }

        $result = $this->find_by_sql($sql);
        return $result;
    }

    /**
     * updates the attributes of this record
     *
     * @param array   $collection         a collection of values in the format [field name] => value with which to initialize this record
     * @param boolean $with_value_changes default 'true'. This allows bypassing the value_changes (??) call
     * @param boolean $clean_the_data     default 'false'. Remove slashencoding; for data coming from $_GET and $_POST. May expand to better cleaning in the future
     *
     * @return AR returns $this
     */
    public function update_attributes($collection = null, $with_value_changes = false, $clean_the_data = false)
    {
        if (!$collection) { // if no row is passed then set the current row in results
            if ($this->results && !MDB2::isError($this->results)) {
                $collection = $this->results->fetchRow();
                $with_value_changes = false;
                $this->dirty = false;
                $this->new = false;
            }
        } else {
            //this object's data is dirty because it is coming from a collection
                $this->dirty = true;
                $with_value_changes = true;
        }

        if ( $collection ) {  // it's possible no collection was set with the DB lookup: checking again.
            //set row variables to properties
            foreach ($collection as $field => $value) {
                if ($clean_the_data) {
                    $value = stripslashes($value);
                }
                if ($with_value_changes) {
                    //apply value changes to this field and value
                    $value_change_result = $this->write_value_changes($field, $value);
                    if ($value_change_result && $field) {
                        $this->values[$field] = $value;
                    }
                } else {
                    $this->values[$field] = $value;
                }
            }
        } else {
            $this->clear_attributes();
        }
        return $this;
    }

    /**
     * clears the properties / attributes of the record and resets it as a clean record;
     *
     * @return AR
     */
    public function clear_attributes()
    {
        if (property_exists($this, 'schema_definition') && is_array($this->schema_definition)) {
            foreach ($this->schema_definition as $attribute => $meta_data) {
                $this->values[$attribute] = null;
            }
        }
        $this->dirty = false;

        return $this;
    }

    function as_collection($fields = null, $key_field = null, $compress_single_field = false)
    {
        if ($this->last_finder_sql_query == '') {
            throw new Exception('as_collection only works on a collection of records.');
            return false;
        }

        if (!$key_field) {
            $key_field = $this->primary_key_field;
        }
        if (!$fields) {
            $fields = $this->display_field;
        }
        if (!is_array($fields)) { //always make an array out of the fields
            $fields = array($fields);
        }
        $result = Array();

        /*
         * get the current index of the MDB2 resultset. Since we are going to be moving around in the recordset
         * I want to be able to get back to the same place later
         */
        $current_index = $this->results->offset;
        $this->results->seek(0); //go to the beginning of the resultset
        while ($record = $this->results->fetchRow()) {
            $row = array();
            /*create an array of all the requested fields */
            foreach ($fields as $field) {
                /* todo: test this functionality */
                if (substr($field, -2) == '()') {
                    $method = substr($field, 0, strlen($field)-2);
                    $row[$field] = $this->$method($record);
                } else {
                    $row[$field] = $record->$field;
                }
            }
            if ($compress_single_field && sizeof($fields == 1)) {
                $result[$record->$key_field] = $row[$fields[0]];
            } else {
                $result[$record->$key_field] = $row;
            }
        }

        /*go back to the index stored earlier */
        $this->results->seek($current_index);
        return $result;
    }

    /**
     * returns a simple array (based on the current recordset criteria) of values for $field
     * as_array is a simplified version of as_collection
     *
     * @param string $field the field name which is the value portion of the array
     *
     * @return array an array of the format [primary_key] => value
     */
    function as_array($field = null)
    {
        return $this->as_collection($field, $this->primary_key_field, true);
    }

    /**
     * returns an array of html <option> tags for a specific field
     *
     * @param variant $selected        the value to mark as the selected option
     * @param string  $field           the field name to use as the option value
     * @param variant $show_all_option defines the first <option> tag.
     *                                 Default is false which doesn't draw an additional tag.
     *                                 'all', true or 'true' draws a tag with '--- Any ---' as the text
     *                                 'none' draws a tag with '--- none ---' as the text
     *                                 'select_one draws a tag with '--- select one ---' as the text
     *
     * @return array an array of html <option> tags
     */
    function as_select_options($selected = null, $field = null, $show_all_option = false)
    {
        $result = '';

        if ($show_all_option === 'all' || $show_all_option === true || $show_all_option == 'true') {
            $result .= '<option value="">-- Any --</option>';
        }
        if ($show_all_option === 'none') {
            $result .= '<option value="">-- none --</option>';
        }
        if ($show_all_option === 'select_one') {
            $result .= '<option value="">-- Select One --</option>';
        }
        $options = $this->as_array($field);
        foreach ($options as $id => $value) {
            $result .= '<option value="'.$id.'"';
            if ($selected && $id == $selected) {
                $result .= ' selected="selected"';
            }
            $result .='>'.$value."</option>";
        }
        return $result;
    }

    /**
     * checks if a model is in the has_one relationship of this object's model
     *
     * @param string $model_name the name of the model to check for
     *
     * @return boolean
     */
    function has_one($model_name)
    {
        if (!property_exists($this, 'has_one')) {
            return false;
        }
        return in_array($model_name, explode(',', $this->has_one));
    }

    /**
     * checks if a model is in the has_many relationship of this object's model
     *
     * @param string $model_name the name of the model to check for
     *
     * @return boolean
     */
    function has_many($model_name)
    {
        /* nested set automatically has many of itself (children) */
        if ($this->acts_as_nested_set && $this->model_name == $model_name) {
            return true;
        }
        if (!property_exists($this, 'has_many')) {
            return false;
        }
        /*first check if it is defined as an array */
        if (is_array($this->has_many)) {
            if (in_array($model_name, array_keys($this->has_many))) {
                return $this->has_many[$model_name];
            } else {
                return false;
            }
        } else {
            return in_array($model_name, explode(',', $this->has_many));
        }
    }

    /**
     * checks if a model is in the belongs_to relationship of this object's model
     *
     * @param string $model_name the name of the model to check for
     *
     * @return boolean
     */
    function belongs_to($model_name)
    {
        /* nested set automatically belongs to itself (parent) */
        if ($this->acts_as_nested_set && $this->model_name == $model_name) {
            return true;
        }

        if (!property_exists($this, 'belongs_to')) {
            return false;
        }
        return in_array($model_name, explode(',', $this->belongs_to));
    }

    /**
     * checks if a model is in the through_model relationship of this object's model
     *
     * @param string $model_name the name of the model to check for
     *
     * @return boolean
     */
    function through_model($model_name)
    {
        if (!property_exists($this, 'has_many_through')) {
            return false;
        }
        if (!$this->has_many_through) {
            return false;
        }
        if (in_array($model_name, array_values($this->has_many_through))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checks if a model is in the has_many_through relationship of this object's model
     *
     * @param string $model_name the name of the model to check for
     *
     * @return boolean
     */
    function has_many_through($model_name)
    {
        if (!property_exists($this, 'has_many_through')) {
            return false;
        }
        if (!$this->has_many_through) {
            return false;
        }
        if (in_array($model_name, array_keys($this->has_many_through))) {
            return $this->has_many_through[$model_name];
        } else {
            return false;
        }

    }

    /**
     * returns a * if a field is required (checks validates_presence_of)
     *
     * @param string $field_name the field name for which we are checking the requirements
     *
     * @return variant returns null if the field is not required and '*' if it is
     */
    function requirements($field_name)
    {
        /*todo flesh out this method to return an english string explaining what the requirements for this field are */
        //debug("validation requirements for $field_name on ".get_class($this));
        if (property_exists($this, 'validates_presence_of')) {
            $required_fields = $this->validates_presence_of;
        } else {
            $required_fields = null;
        }

        if (is_array($required_fields) && in_array($field_name, $required_fields)) {
            return '*';
        }
    }

    /**
     * checks that this record is valid
     *
     * @return boolean true is valid, false is invalid
     */
    function is_valid()
    {
        /*todo flesh this out with other validation methods. Put in validations class? */
        $validation_result = array();
        if (property_exists($this, 'validates_presence_of')) {
            foreach ($this->validates_presence_of as $required_field) {
                $required_field = trim($required_field);
                if (
                    !isset($this->$required_field) ||
                    !$this->$required_field ||
                    $this->$required_field == ""
                ) {
                    $human_field_name = $required_field;
                    if (substr($human_field_name, -3) == '_id') {
                        $human_field_name = substr($human_field_name, 0, strlen($human_field_name)-3);
                    } //todo fix this hack. need a new validation for associated records, natch.
                    $validation_result[$required_field]['message'] = humanize($human_field_name)." is empty";
                    $validation_result[$required_field]['error_type'] = 'error';
                }
            }
        }
        if (method_exists($this, 'validate')) {
            $custom_validation_result = $this->validate();
            if ($custom_validation_result) {
                $validation_result = array_merge($validation_result, $custom_validation_result);
            }
        }
        if (isset($validation_result) && sizeof($validation_result) > 0) {
            $this->validation_result = $validation_result;
            $this->validation_errors = 'Error:';
            foreach ($this->validation_result as $single_result) {
                $this->validation_errors .= '<br />'.$single_result['message'];
            }
            return false;
        }
        return true;
    }

    /**
     * does a sum of all the values for a specific sum_field in this recordset.
     * Loops through the current collection, so finder criteria are naturally
     * taken into account
     *
     * @return float the sum value
     */
    function sum($sum_field = null)
    {
        if (!$sum_field) {
            if (!property_exists($this, 'sum_field')) {
                return false;
            }
            $sum_field = $this->sum_field;
        }
        if ($this->count == 0) {
            return 0;
        }


        /*
         * get the current index of the MDB2 resultset, since we are going to be
         * messing with it; I want to come back to the same place later
         */
        $current_index = $this->key();
        /* go to the beginning of the resultset */
        $this->rewind();

        $sum = 0;
        foreach ($this as $record) {
            $sum += $this->values[$sum_field];
        }

        /* go back to the index stored earlier */
        $this->seek($current_index);

        return $sum;
    }

    /**
     * shows a 'friendly' name for this record.
     * for example: $customer->display_name() might show first_name and last_name
     * because the model has a custom display_name method.
     * AR looks for fields in the schema named title, name or id (in that order)
     * and sets one of those as the display field
     *
     * @return string the display_field value of the current record, or false if no records in this recordset
     */
    function display_name()
    {
        if ($this->count == 0) {
            return false;
        }
        return $this->values[$this->display_field];
    }

    /**
     * checks a recordset for errors
     *
     * @param resultset $result       an MDB2 resultset
     * @param boolean   $die_on_error default is true. If true it throws an exception if there
     *                                is an error with the recordset otherwise it returns null
     *
     * @return variant the MDB2 resultcode
     */
    function error_check($result, $die_on_error = true)
    {
        if (PEAR::isError($result) || MDB2::isError($result)) {
            if ($die_on_error) {
                /*die('<pre>'.$result->getMessage().' - '.
                 * $result->getUserinfo()).'</pre>';
                 */
                throw new Exception(
                    $result->getMessage().' - '.$result->getUserinfo()
                );
                return null;
            } else {
                return $result->code;
            }
        }
    }

    /**
     * returns the current AR object
     *
     * @return AR
     */
    function current()
    {
        return $this;
    }

    /**
     * returns the key for this record
     *
     * @return integer
     */
    function key()
    {
        if ($this->valid()) {
            return $this->offset;
        }
    }

    /**
     * seeks to a specific record
     *
     * @param integer $index the key of the record to seek to
     *
     * @return boolean
     */
    function seek($index)
    {
        if ($this->valid()) {
            $this->results->seek($index);
            $this->offset = $index;

            $this->update_attributes();

            return true;
        }
        return false;
    }

    /**
     * checks that this is a valid record
     *
     * @return boolean
     */
    function valid()
    {
        if ($this->count > 0
            && !MDB2::isError($this->results)
                && $this->offset < $this->count
        ) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * moves to the beginning of the collection
     *
     * @return void
     */
    function rewind()
    {
        /*
         * I am not using valid() because valid checks offset.
         * when at the end it could never rewind!
         */
        if ($this->count > 0 && !MDB2::isError($this->results)) {
            $this->offset = 0;
            $this->seek(0);
        }
    }

    /**
     * selects the next record in the collection
     *
     * @return void
     */
    function next()
    {
        $this->seek($this->offset+1);
    }

    //todo put this somewhere else!
    //this is used in functions.php
    static $sql_phrases = array(
        'SELECT'    => ', ',
        'FROM'      => ', ',
        'WHERE'     => ' ',
        'GROUP BY'  => ', ',
        'ORDER BY'  => ','
    );
}

if (!defined('SQL_INSERT_DATE_FORMAT')) {
    define('SQL_INSERT_DATE_FORMAT', '%Y-%m-%d');
}
if (!defined('SQL_INSERT_DATE_TIME_FORMAT')) {
    define('SQL_INSERT_DATE_TIME_FORMAT', '%Y-%m-%d %R');
}

/**
 * compares two arrays or two AR objects returning an array of changed attributes
 *
 * @param variant $record1             an array in the format [field name] => value or an AR object
 * @param variant $record2             an array in the format [field name] => value or an AR object
 * @param boolean $include_boilerplate It automatically ignores id, updated_on,
 *                                      created_on, revision and user_id unless
 *                                      include_boilerplate is set to true
 *
 * @return array a 1 dimensional array of changed attributes
 */
function compare_records($record1, $record2, $include_boilerplate = false)
{
    /* todo: return the changed values too */
    if (!is_array($record1)) {
        $record1 = $record1->values;
    }
    if (!is_array($record2)) {
        $record2 = $record2->values;
    }
    $changed_attributes = null;
    //todo check that these are the same type of object
    foreach ($record1 as $attribute => $attribute_value) {
        if ($record1[$attribute] != $record2[$attribute]) {
            switch ($attribute) {
            case 'id':
            case 'updated_on':
            case 'created_on':
            case 'revision':
            case 'user_id':
                if (!$include_boilerplate) {
                    break;
                }
            default:
                $changed_attributes[] = $attribute;
            }
        }
    }
    return $changed_attributes;

}
/*
 * docs - todo make this use a proper php documenting standard
 *
 * more callbacks todo:
 * before_delete (great for cancelling delete and marking as "deleted")
 * after_delete
 *
 * must AR always have a schema table ?
 */
?>
