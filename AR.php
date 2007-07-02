<?php
/**
 * This file implements the ActiveRecord pattern
 * @author Gavin van Lelyveld <gavin@pedantic.co.za>
 * @version 2.0
 * @package pedantic/lib
 */

define ('SQL_INSERT_DATE_FORMAT', '%Y-%m-%d');

class AR implements SeekableIterator # basic AR class
{
    private $offset = 0; private $dirty = false; private $results;
    public $count = 0; public $attributes = null;
    public $validation_result = null;
    public $preserve_updated_on = false;
    public $db = null, $schema_definition;

    function connect_to_db($dsn = null)
    {
        if (!$dsn) {$dsn = App::$env->dsn;}
        $this->db =& MDB2::Connect($dsn);
        App::error_check($this->db);
        $this->db->setFetchMode(MDB2_FETCHMODE_OBJECT);
    }

    function __construct($collection = null, $with_value_changes = true)
    {
        if (!$this->db)
        {
            $this->connect_to_db();
        }
        
        #get the model name
        $this->model = get_class($this);
       
        #pull in the schema definition
            $this->schema_definition = App::$schema_definition[$this->model];

        if (!isset($this->primary_key_field)) {$this->primary_key_field = 'id';}
        
        #check if this model is a changelog
        if (!isset($this->primary_table)) {
            $changelog_pos = strpos($this->model, '_changelog'); #todo fix this hacky method. right ?

            if ($changelog_pos > 0)
            {
                $this->primary_table = tableize(pluralize(substr($this->model, 0, $changelog_pos))).'_changelog';
            }
            else
            {
                $this->primary_table = tableize(pluralize($this->model));
            }
        }

        if (!isset($this->display_field)) {$this->display_field = 'name';}

        #split the validations - todo. maybe use getobjectvars todo add all validations
            $validations = array('validates_presence_of');
            foreach ($validations as $validation)
            {
                if (isset($this->$validation))
                {
                    $this->$validation = split(',',$this->$validation);
                }
            }
        if ($collection) {$this->update_attributes($collection, $with_value_changes);} #updates attribs if object is created with a collection
    }

    function __call($method_name, $params)
    {
        #overload finders
        if (substr($method_name, 0, 8) == 'find_by_')
        {
            $find_by = substr($method_name, 8);
            $this->find("WHERE $find_by = '$params[0]'"); #todo expand this to multiple params
        }
    }

    function __get($name)
    {
    #relationships magic
        #todo other relationships ?
        if ($this->has_one($name))
        {
            $has_one = new $name;
            $fk = foreign_keyize($name);
            $has_one->find($this->$fk);
            return $has_one;
        }
        #check for properties with this name
        elseif (is_array($this->schema_definition) && in_array($name, array_keys($this->schema_definition)))
        {
            return null;#it isn't set for some reason
        }
        else
        {
            trigger_error("<i>$name</i> is not a relationship or a or property of <i>".get_class($this).'</i>', E_USER_ERROR); 
        }


    }

    function create() #create a new record. analogous to ROR new method
    {
        #todo check if this function is used for it's described purpose; may be obsolete
        $this->new_record = true;
        $this->clear_attributes();
    }

    function update()
    {
        return $this->save('update');
    }

    function save($save_type = "save")
    {
        #execute before validation actions
            if (method_exists($this, 'before_validation')) { $this->before_validation(); }
            if (method_exists($this, 'before_validation_on_'.$save_type)) { $this->{'before_validation_on_'.$save_type}(); }

        #validate object
            $allow_save = $this->is_valid();

        #execute before save/update actions
            if (method_exists($this, 'before_'.$save_type)) { $this->{'before_'.$save_type}(); }

        if ($allow_save)
        {
            #build the fields, values arrays from attributes
                $fields = $this->attributes;
                #debug(get_class($this));debug($save_type);print_r($fields);
                #remove the PK from attributes
                    $pk_index = array_search($this->primary_key_field, $fields);
                    if (!($pk_index === false)) { unset($fields[$pk_index]); $fields = array_values($fields); }

                foreach ($fields as $attribute)
                {
                    $values[] = addslashes($this->$attribute);
                }
                if($save_type == 'update') 
                {
                    if (!isset($this->updated_on) || $this->updated_on == true) #remove updated_on when updating so we can re-add it
                    {
                        if (!$this->preserve_updated_on)
                        {
                            $updated_on_index = array_search('updated_on', $fields);
                            if (!($updated_on_index === false)) { unset($fields[$updated_on_index]); unset($values[$updated_on_index]); }
                        }
                    }
                }
                $now = strftime(SQL_DATE_TIME_FORMAT, time());
                if ($save_type == 'save')
                {
                    if (!isset($this->created_on) || $this->created_on == true) {$fields[] = 'created_on';$values[] = $now;} 
                }

                if (!$this->preserve_updated_on) {if (!isset($this->updated_on) || $this->updated_on == true) {$fields[] = 'updated_on';$values[] = $now;}}
                

            if ($save_type == "save")
            {
                $record_id = $this->save_core($fields, $values);
            }
            elseif ($save_type == "update")
            {
                $record_id = $this->update_core($fields, $values);
            }
            #okay... do we have a a changelog?
                if (property_exists($this->model, 'changelog')){ $this->changelog($record_id, $fields, $values);}

        #execute after save/update actions
            if (method_exists($this, 'after_'.$save_type)) { $this->{'after_'.$save_type}(); }

            return $record_id;
        }
        else
        {
            return false;
        }
    }
    
    private function save_core($fields, $values)
    {
        $fields = implode(',', $fields); $values = "'".implode("','", $values)."'";
        $sql = "INSERT INTO ".$this->primary_table." ($fields) VALUES ($values)";
        #debug ( $sql );
        $save = $this->db->query($sql);App::error_check($save);
        
        #get the key of the new record
            $record_id = $this->db->lastInsertID($this->primary_table,$this->primary_key_field);
        return $record_id;
    }

    private function update_core($fields, $values)
    {
           $values = array_map("enquote", $values);

           #print_r($fields); print_r($values);

           $update_sql = implode_with_keys(',', array_combine($fields, $values), "");
           $sql = 'UPDATE '.$this->primary_table." SET $update_sql WHERE ".$this->primary_key_field."=".$this->{$this->primary_key_field};
           #debug($sql);
           $update = $this->db->query($sql);App::error_check($update);
           return $this->{$this->primary_key_field};
    }
    
    function save_multiple($collection)
    {
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

        for ( $i=0; $i < sizeof($new_records); $i++) #for each of the non-array values of the collection...
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
    function delete_by_sql($sql)
    {
        $this->last_sql_query = $sql; 
        $this->results = $this->db->query($sql);
        if ( $this->results )
        {
            $this->count = 0;
            $this->clear_attributes();
            App::error_check($this->results);
            return true;
        }
        return false;
    }

    function delete($criteria)
    {
        #debug($criteria);
        $sql_criteria = $this->criteria_to_sql($criteria);
        $sql = "DELETE FROM ".$this->primary_table.' '.$sql_criteria;
        #debug($sql);
        $this->delete_by_sql($sql);
    }

    function changelog($record_id, $fields, $values)
    {

         #get the highest revision id
                 $sql = "SELECT MAX(revision) as rev_id, MAX(created_on) as created_on FROM ".$this->primary_table."_changelog WHERE ".$this->model.'_id'." = '".$record_id."'";
                 #debug($sql);
                 $rev_result = $this->db->query($sql);App::error_check($rev_result);
                 if ($rev_result)
                 {
                     $rev_result = $rev_result->fetchRow();

                     $revision = $rev_result->rev_id +1;
                     $created_on = $rev_result->created_on;
                 }
                 else {$revision = 1;}

            #record_id
                 $fields[] = $this->model.'_id'; $values[] = $record_id;
            #revision
                 $fields[] = 'revision'; $values[] = $revision;
            #revision
                if ($created_on && !in_array('created_on', $fields))
                {
                    $fields[] = 'created_on'; $values[] = $created_on;
                }

        $fields = implode(',', $fields); $values = "'".implode("','", $values)."'";

        $sql = "INSERT INTO ".$this->primary_table."_changelog ($fields) VALUES ($values)";
        #debug ( $sql );
        $save = $this->db->query($sql);App::error_check($save);
    }

    function write_value_changes(&$field, &$value)
    {
        switch ($field)
        {
            case 'password_md5':
                if ($value != '' && $this->dirty)
                {
                    if (method_exists($this,'encrypt_password')) #is this a hack ? no... no it's not. it makes sense, right ?
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
            case 'session_user_id': #todo fix hack. should check the model if it has a user id, elswhere
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

    function find_by_sql($sql)
    {
        $this->last_sql_query = $sql; 
        $this->results = $this->db->query($sql);
        if ( $this->results )
        {
            App::error_check($this->results);

            $this->count = $this->results->numRows(); #count
            $this->offset = 0;
            $this->update_attributes();
        }
        else
        {
            $this->count = 0;
            $this->clear_attributes();
        }
    }

    function find($criteria) #todo dynamic finders!
    {
        #echo 'criteria';debug($criteria);
        $sql_criteria = $this->criteria_to_sql($criteria);
        #echo 'sql_criteria'; debug($sql_criteria);
        $sql = "SELECT * FROM ".$this->primary_table.' '.$sql_criteria;
        #debug($sql);
        $this->find_by_sql($sql);
    }

    protected function dirty()
    {
        $this->dirty = true;
        #don't do any of this. It kills an update loop.
        #$this->results = null;$this->offset = 0;$this->count = 1;
    }
    public function update_attributes($collection = null, $with_value_changes = false)
    {
        if (!$collection ) # if no row is passed then set the current row in results
        {
            if ($this->results && !MDB2::isError($this->results))
            {
                $collection = $this->results->fetchRow();
            }
        }
        else
        {
            #this object's data is dirty because it is coming from a collection
                $this->dirty();
                $with_value_changes = true;
        }
        if ($collection)  # it's possible no collecton was set with the DB lookup: checking again.
        {
        #set row variables to properties
            if ($this->attributes == null) {$update_attributes_list = true;} else {$update_attributes_list = false;} #this check is outside the loop, else it will only update the first attribute!
            foreach ($collection as $field => $value)
            {   
                if ($with_value_changes)
                {
                    $value_change_result = $this->write_value_changes($field, $value);
                    if ($value_change_result && $field) {$this->$field = $value;}
                    if ($update_attributes_list && $field){$this->attributes[] = $field;} #TODO only set this when not dirty. this is currently a workaround for link tables
                }
                else
                {
                    $this->$field = $value;
                    if ($update_attributes_list){$this->attributes[] = $field;} #set list of attribute names if not already set #TODO only set this when not dirty
                }
            }
            return true;
        }
        else
        {
            $this->clear_attributes(); return false;
        }
    }

    function clear_attributes()
    {
        if (isset($this->attributes))
        {
            foreach ($this->attributes as $attribute)
            {
                unset($this->$attribute);
            }
        }
    }

    function as_array($field = null, $criteria = null)
    {
        if (!$field) {$field = $this->display_field;}
        $result = Array();
       #todo lose the criteria and the custom sql. use finders and iterators 
        $sql = "SELECT *, ".$this->primary_table.'.' .$this->primary_key_field." as __pk_field FROM ".$this->primary_table;
        if ($criteria) {$sql .= ' '.$criteria;}
        #debug($sql);
        $options = $this->db->query($sql);
        if (!MDB2::isError($options))
        {
            while ($row = $options->fetchRow())
            {
                if (substr($field, -2) == '()')
                {
                    $method = substr($field, 0, strlen($field)-2);
                    $result[$row->__pk_field] = $this->$method($row);
                }
                else
                {
                    $result[$row->__pk_field] = $row->$field;
                }
            }
        }
        return $result;
    }
    function as_select_options($selected = null, $field = null, $show_all_option = false, $criteria = null)
    {
        $result = '';

        if ($show_all_option === 'all' || $show_all_option === true || $show_all_option == 'true') { $result .= '<option value="">-- Any --</option>'; }
        if ($show_all_option === 'none') { $result .= '<option value="">-- none --</option>'; }
        if ($show_all_option === 'select_one') { $result .= '<option value="">-- Select One --</option>'; }
        $options = $this->as_array($field, $criteria);
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

    function has_one($model_name)
    { 
        if (!isset($this->has_one)) {return false;}
        return in_array($model_name, split(',',$this->has_one));
    }

    function through_model($model_name) #todo use a better name
    {
        if (!isset($this->has_many_through)) {return false;}
        if (in_array($model_name, array_values($this->has_many_through)))
        {
            return true;
        }
        else
        {
            return false;
        }

    }
    function has_many_through($model_name)
    {

        if (!isset($this->has_many_through)) {return false;}
        if (in_array($model_name, array_keys($this->has_many_through)))
        {
            return $this->has_many_through[$model_name];
        }
        else
        {
            return false;
        }
    
    }

    function requirements($field_name)
    {
        #todo flesh out this method
        #this method returns an english string explaining what the requirements for this field are... well, it will one day when I get there :)
        #debug("validation requirements for $field_name on ".get_class($this));
        if (isset($this->validates_presence_of)) { $required_fields = $this->validates_presence_of; } else { $required_fields = null; }
        
        if (is_array($required_fields) && in_array($field_name, $required_fields))
        {
            return '*';
        }
    }

    #todo flesh this out with other validation methods. Put in validations class?
    function is_valid()
    {
        $validation_result = array();
        if (isset($this->validates_presence_of))
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

    function criteria_to_sql($criteria) #this method takes dynamic criteria and converts it to SQL 
    {
        if (is_numeric($criteria)) {$sql_criteria = 'WHERE '.$this->primary_table.'.'.$this->primary_key_field.'='.$criteria;} #if passed a numeric value assume it's a Primary Key
        elseif (is_string($criteria)) 
        {
            if (strtolower($criteria)== 'all') 
            {
                $sql_criteria = ' WHERE 1=1';
            }
            else
            {
                $sql_criteria = $criteria;
            }
        }
        return $sql_criteria;
    }

    function display_name()
    {
        return $this->{$this->display_field};
    }
    
    #methods required for iterator implementation
    function current()
    {
        #echo "current";
        return $this;
    }
    function key()
    {
        #echo "key";
        if ($this->valid())
        {
            return $this->offset;
        }
    }
    function seek($index)
    {
        #echo "seeking to $index<br />";
        if ($this->valid())
        {
            $this->results->seek($index);
            $this->offset = $index;

            $this->update_attributes();

            return true;
        }
        return false;
    }
    function valid()
    {
        #echo "valid";
        if ($this->count > 0 && !MDB2::isError($this->results) && $this->offset < $this->count) { return true; } else { return false; }

    }
    function rewind()
    {
        #echo "rewind";
        if ($this->count > 0 && !MDB2::isError($this->results)) #not using valid() because valid checks offset. when @ end it could never rewind then!
        {
            $this->offset = 0;
            $this->seek(0);
        }
    }
    function next()
    {
        #echo "next";
        $this->seek($this->offset+1);
    }

}
function compare_records($record1, $record2, $include_boilerplate = false)
{
    $changed_attributes = null;
    #todo check that these are the same type of object
    foreach($record1->attributes as $attribute)
    {
        if ($record1->$attribute != $record2->$attribute)
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
# TODO
# auto-pull schema info from database
# cache the schema info.. to a php script

 /*function __call($method, $params)
    {
        echo $method;
        print_r($params);
        die();
    }*/


#docs - todo make this use a proper php documenting standard
#
#more callbacks todo:
#before_delete (great for cancelling delete and marking as "deleted")
#after_delete
?>
