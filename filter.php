<?
class filter
{
    function init($primary_model, $filters)
    {
        $this->primary_model = $primary_model;
        #clean up the filters array
        foreach ($filters as $filter)
        {
            $name_alias = split_aliased_string($filter[0]);
            $name = array_keys($name_alias);$name = $name[0];
            $filter['name'] = $name;
            $filter['alias'] = $name_alias[$name];
            #set the type
                $filter['type'] = $filter[1];
            unset($filter[0]);unset($filter[1]);
            $this->filters[$name] = $filter;
        }
        $this->get_filter_values();
    }

    function get_filter_values()
    {
        foreach(array_keys($_GET) as $getvar)
        {
            if (substr($getvar, 0, 7) == 'filter_')
            {
                $filter_field = substr($getvar, 7);
                $filter_value = $_GET[$getvar];
                if ($filter_value != '')
                {
                    $this->has_filter_values = true;
                    $this->filter_values[] = Array('name' => $filter_field, 'value' => $filter_value);
                }
            }
        }
    }
    function match_text($no_of_records)
    {
        $result = array();
        $pre_text = '';
        if (!$this->filter_values) {return '';}
        #print_r($this->filter_values);die();
        $pre_text .= " match"; if ($no_of_records == 1) {$pre_text .= 'es';}; $pre_text .= ' ';
        foreach ($this->filter_values as $filter_value)
        {
            //check if this is a select filter and an int and look up the value
            $filter = $this->filters[$filter_value['name']];
            if ($filter['type'] == 'select') 
            {
                if (is_numeric($filter_value['value']))
                {
                    #todo determining the model here and in filter_select should be in a method
                    if (isset($filter['model']))
                    {
                        $class_name = $filter['model'];
                    }
                    else
                    {
                        $class_name = singularize($filter_value['name']);
                    }
                    $options = new $class_name;
                    $options->find($filter_value['value']);
                    $display_field = 'name'; if (isset($filter['field'])) {$display_field = $filter['field'];}
                    if (substr($display_field, -2) == '()') 
                    {
                        $display_field = substr($display_field, 0, strlen($display_field)-2); $display_value = $options->$display_field();
                    }
                    else
                    {
                        $display_value = $options->$display_field;
                    }
                    $result[] = '<i>'.humanize($this->filters[$filter_value['name']]['alias']) . "</i> of \"" .$display_value."\""; 
                }
                else
                {
                    $result[] = '<i>'.humanize($this->filters[$filter_value['name']]['alias']) . "</i> of \"" .$filter_value['value']."\""; 
                }
            } 
            else
            {
                $result[] = '<i>'.proper_nounize($this->filters[$filter_value['name']]['alias']) . "</i> of \"" .$filter_value['value']."\""; 
            }
        }
        return $pre_text.to_sentence($result);
            
    }
    function sql_criteria()
    {
        #leave if we don't have any sql criteria to build
            if (!$this->has_filter_values) {return null;}

            #we need an actual instance of the primary model, because properties like PK Field are created on construct
            $model_object = new $this->primary_model;

        $sql = Array('FROM' => array(), 'WHERE' => array());

        foreach ($this->filter_values as $filter_value)
        {
            $name = $filter_value['name'];
            #if it's a text filter
            if ($this->filters[$name]['type'] == 'text')
            {
                $sql['WHERE'][] = str_replace('~', '.', $filter_value['name'])." LIKE '%".$filter_value['value']."%'";
            }
            if ($this->filters[$name]['type'] == 'date')
            {
                $sql['WHERE'][] = str_replace('~', '.', $filter_value['name'])." LIKE '".
                strftime(SQL_DATE_FORMAT, strtotime((string)$filter_value['value']))."%'"; #warning this format might bed to be specific to doing where criteria, and not the display friendly format
            }
            if ($this->filters[$name]['type'] == 'select')
            {
                #check what relationship it is to the primary model
                $primary_model_object = new $this->primary_model; 
                $lnk_model = $primary_model_object->has_many_through($name);
                if ($lnk_model)
                {
                    $lnk_model_object = new $lnk_model;
                    $lnk_model_table = $lnk_model_object->primary_table;
                    
                    #meet up with the link table, dog
                    $filter_field = $lnk_model_table.'.'.foreign_keyize(singularize($name));
                    $from = "INNER JOIN $lnk_model_table on $lnk_model_table."
                        .foreign_keyize($this->primary_model)." = ".pluralize($this->primary_model).".".$model_object->primary_key_field;
                    $sql['FROM'][] = $from;
                }
                else
                {
                    $filter_field = foreign_keyize(singularize($name));
                }
                $sql['WHERE'][] = str_replace('~', '.', $filter_field)."='".$filter_value['value']."'";
            }
        }
        
        return $sql;
        if ( $return )
        {
            $return = "( ".substr( $return, 5 ). " )";
        }
    }

    function filter_select($filter)
    {
        $name = $filter['name']; $alias = $filter['alias'];
        if (isset($filter['model']))
        {
            $foreign_model_name = $filter['model'];
        }
        else
        {
            $foreign_model_name = singularize($name);
        }
        $options = new $foreign_model_name;
        $get_var = 'filter_'. $name;
        if ($_GET[$get_var] != '') {$value = $_GET[$get_var];} else {$value = null;}
        $options = $options->as_select_options($value, $filter['field'], true, $filter['criteria']);
        ?><label for="filter_<?=$name?>"><?=humanize($alias)?></label><select id="filter_<?=$name?>" name="<?=$get_var;?>"><?=$options;?></select><?
    }

    function filter_text($filter)
    {
        $name = $filter['name']; $alias = $filter['alias'];
        if ($_GET["filter_$name"] != '') {$value = $_GET["filter_$name"];} else {$value = null;}
        ?><label for="filter_<?=$name?>"><?=humanize($alias)?></label><input type="text" id="filter_<?=$name?>" name="filter_<?=$name?>" value="<?=$value;?>" /><?
    }
    function filter_date($filter)
    {
        $name = $filter['name']; $alias = $filter['alias'];
        ?><label for="filter_<?=$name?>"><?=humanize($alias)?></label><input readonly="readonly" type="text" id="filter_<?=$name?>" name="filter_<?=$name?>" /><input type="button" id="filter_<?=$name;?>_button" value="..." />
    <script type="text/javascript">
        Calendar.setup({
            inputField     :    "filter_<?=$name?>",     // id of the input field
            ifFormat       :    "<?=DATE_FORMAT?>",      // format of the input field
            button         :    "filter_<?=$name?>_button",  // trigger for the calendar (button ID)
            align          :    "Tl",           // alignment (defaults to "Bl")
            singleClick    :    true
        });
    </script><?
    }

}
?>
