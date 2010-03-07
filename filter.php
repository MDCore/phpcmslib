<?
class filter {
  function init($primary_model, $filters) {
    $this->primary_model = $primary_model;
    #clean up the filters array
    foreach ($filters as $filter) {
      $db_field_name_and_alias = split_aliased_string($filter[0]);
      $db_field_name = array_keys($db_field_name_and_alias); $db_field_name = $db_field_name[0];
      $filter['db_field_name'] = $db_field_name;
      $filter['alias'] = $db_field_name_and_alias[$db_field_name];
      #set the type
      $filter['type'] = $filter[1];
      unset($filter[0]);unset($filter[1]);
      $this->filters[$filter['alias']] = $filter;
    }
    $this->get_filter_values();
  }

  function get_filter_values() {
    if (isset($_GET)) {
      foreach(array_keys($_GET) as $getvar) {
        if (substr($getvar, 0, 7) == 'filter_') {
          $filter_field = substr($getvar, 7);
          $filter_value = $_GET[$getvar];
          if ($filter_value != '')  {
            $this->has_filter_values = true;
            $this->filter_values[] = Array('alias' => $filter_field, 'value' => $filter_value);
          }
        }
      }
    }
  }
  function match_text($no_of_records) {
    $result = array();
    $pre_text = '';
    if (!$this->filter_values) { return ''; }
    #print_r($this->filter_values);die();
    $pre_text .= " match"; if ($no_of_records == 1) {$pre_text .= 'es';}; $pre_text .= ' ';
    foreach ($this->filter_values as $filter_value) {
      //check if this is a select filter and an int and look up the value
      $filter = $this->filters[$filter_value['alias']];
      if ($filter['type'] == 'select') {
        if (is_numeric($filter_value['value'])) {
          #todo determining the model here and in filter_select should be in a method
          if (isset($filter['model'])) {
            $class_name = $filter['model'];
          } else {
            $class_name = $filter['db_field_name'];
          }
          $options = new $class_name;
          $options->find($filter_value['value']);
          $display_field = 'db_field_name';
          if (isset($filter['field'])) { $display_field = $filter['field']; } else { $display_field = $options->display_field; }
          if (substr($display_field, -2) == '()') {
            $display_field = substr($display_field, 0, strlen($display_field)-2);
            $display_value = $options->$display_field();
          } else {
            $display_value = $options->$display_field;
          }
          $result[] = '<i>'.humanize($this->filters[$filter_value['alias']]['alias']) . "</i> of \"" .$display_value."\"";
        } else {
          $result[] = '<i>'.humanize($this->filters[$filter_value['alias']]['alias']) . "</i> of \"" .$filter_value['value']."\"";
        }
      } else {
        $result[] = '<i>'.proper_nounize($this->filters[$filter_value['alias']]['alias']) . "</i> of \"" .$filter_value['value']."\"";
      }
    }
    return $pre_text.to_sentence($result);
  }

  function sql_criteria() {
      #leave if we don't have any sql criteria to build
          if (!$this->has_filter_values) { return null; }

          #we need an actual instance of the primary model, because properties like PK Field are created on construct
          $model_object = new $this->primary_model;

      $sql = Array('FROM' => array(), 'WHERE' => array());
      foreach ($this->filter_values as $filter_value) {

        $alias = $filter_value['alias'];
        $db_field_name = $this->filters[$alias]['db_field_name'];

        #if it's a text filter
        if ($this->filters[$alias]['type'] == 'text') {
          $sql['WHERE'][] = str_replace('~', '.', $db_field_name)." LIKE '%".$filter_value['value']."%'";
        }
        if ($this->filters[$alias]['type'] == 'date') {
          $sql['WHERE'][] = str_replace('~', '.', $db_field_name)." LIKE '".
          strftime(SQL_DATE_FORMAT, strtotime((string)$filter_value['value']))."%'"; #warning this format might be specific to doing where criteria, and not the display friendly format
        }

        if ($this->filters[$alias]['type'] == 'select') {
          #check what relationship it is to the primary model
          $primary_model_object = new $this->primary_model;
          $lnk_model = $primary_model_object->has_many_through($db_field_name);
          if ($lnk_model) {
            $lnk_model = singularize($lnk_model);
            $lnk_model_object = new $lnk_model;
            $lnk_model_table = $lnk_model_object->schema_table;

            #meet up with the link table, dog
            $filter_field = $lnk_model_table.'.'.foreign_keyize(singularize($db_field_name));
            $from = "INNER JOIN $lnk_model_table on $lnk_model_table."
                .foreign_keyize($this->primary_model)." = ".pluralize($this->primary_model).".".$model_object->primary_key_field;
            $sql['FROM'][] = $from;
          } else {
            $filter_field = foreign_keyize($db_field_name);
          }
          $sql['WHERE'][] = pluralize($this->primary_model).'.'.str_replace('~', '.', $filter_field)."='".$filter_value['value']."'";
        }
      }
      return $sql;
  }

  function filter_select($filter) {
    $db_field_name = $filter['db_field_name']; $alias = $filter['alias'];

    if (isset($filter['model'])) {
      $foreign_model_name = $filter['model'];
    } else {
      $foreign_model_name = $db_field_name;
    }

    $options = new $foreign_model_name;
    $get_var = 'filter_'. $alias;
    if ($_GET[$get_var] != '') {
      $value = $_GET[$get_var];
    } else {
      $value = null;
    }
    if ($filter['criteria'] == '') {
      $filter['criteria'] = 'all';
    }
    $options = $options->find($filter['criteria'], $filter['additional_sql_options'])->as_select_options($value, $filter['field'], true);
    ?><label for="filter_<?=$alias;?>"><?=humanize($alias);?></label><select id="filter_<?=$alias;?>" name="<?=$get_var;?>"><?=$options;?></select><?
  }
  function filter_text($filter) {
    $db_field_name = $filter['db_field_name']; $alias = $filter['alias'];
    if ($_GET["filter_$alias"] != '') {
      $value = $_GET["filter_$alias"];
    } else {
      $value = null;
    }
    ?><label for="filter_<?=$alias;?>"><?=humanize($alias); ?></label><input type="text" id="filter_<?=$alias;?>" name="filter_<?=$alias;?>" value="<?=$value;?>" /><?
  }
  function filter_date($filter) {
    $db_field_name = $filter['db_field_name']; $alias = $filter['alias'];
    if ($_GET["filter_$alias"] != '') {
      $value = $_GET["filter_$alias"];
    } else {
      $value = null;
    }
    ?><label for="filter_<?=$alias;?>"><?=humanize($alias);?></label><input readonly="readonly" type="text" id="filter_<?=$alias;?>" value="<?=$value;?>" name="filter_<?=$alias;?>" /><input type="button" id="filter_<?=$alias;?>_button" value="..." />
    <script type="text/javascript">
      Calendar.setup({
        inputField     :    "filter_<?=$alias;?>",     // id of the input field
        ifFormat       :    "<?=DATE_FORMAT?>",      // format of the input field
        button         :    "filter_<?=$alias;?>_button",  // trigger for the calendar (button ID)
        align          :    "Tl",           // alignment (defaults to "Bl")
        singleClick    :    true
      });
    </script><?
  }
}
?>
