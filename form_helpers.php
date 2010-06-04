<?
/* TODO
 * - refactor draw_element
 * - cleanup this while class!!!
 */
class forms
{
    function name_to_id($name)
    {
        $name = str_replace('[', '_', $name);
        $name = str_replace(']', '', $name);
        return $name;
    }

    function upload($name, $value = null, $attributes = null)
    {
        $page = App::$controller;
        $result = '';

        if (isset($attributes['preview']) && $attributes['preview']) {
            $preview = true;
        } else {
            $preview = false;
        }

        if ($page->action == 'edit') {
            //preview this sucker
            $upl = new upload;
            $upl->load($value['model'], $value['field_name'], $value['record_id']);
            if ($preview) {
                /* previewed uploads */
                if ($upl->file_exists()) {
                    $result .= '<a href="'.$upl->display_filename().'" target="_blank"><img src="'.$upl->display_filename().'" style="width: 100px;" /><br />'.$upl->original_filename.'</a><br />';
                } else {
                    $result .= '<i>No '.proper_nounize($upl->field_name).' uploaded</i><br />';
                }
            } else {
                /* uploads that are not being previewed */
                if ($upl->file_exists()) {
                    $result .= 'View: <a href="'.$upl->display_filename().'" target="_blank">'.$upl->original_filename.'</a><br />';
                } else {
                    $result .= '<i>No '.proper_nounize($upl->field_name).' uploaded</i><br />';
                }
            }
            $result .="<input id=\"".$name."\" type=\"file\" name=\"$name\"";
            $result .= self::parse_attributes($attributes, array('preview', 'show_note'));
            $result .=" />";
        } else {
            //
            $result .="<input id=\"".$name."\" type=\"file\" name=\"$name\"";
            $result .= self::parse_attributes($attributes, array('preview', 'show_note'));
            $result .=" />";
        }
        if (!isset($attributes['show_note']) | (isset($attributes['show_note']) && $attributes['show_note'] == true)) {
            $result .= "<div>Note: You cannot upload individual files larger than ".ini_get('upload_max_filesize'). " and the entire upload will fail if larger than ".ini_get('post_max_size').".</div>";
        }
    return $result;
    }

    /*
     * usage:
     * array('categories', 'multi_select'),
     */
    function multi_select($name, $values = null, $attributes = null, $options = null)
    {
        $result = '';
        if (sizeof($options)> 0) {
            $element_name = $name; $element_name = str_replace( '[', '_', $element_name); $element_name = str_replace( ']', '_', $element_name);
            $result .= "<div id=\"".$element_name."_container\">";

            $result .= '<table><tr>';
            /* the select all checkbox */
            $result .= "<td><input name =\"".$name."[]\" class=\"checkbox\" type=\"checkbox\" id=\"".$element_name."_all\" onclick=\"
                var f = $('div#".$element_name."_container input[type=checkbox]');
            	for(var i=0; i<f.length; i++){
                    if (this.checked)
                    {
                        f[i].checked = true;
                    }
                    else
                    {
                        f[i].checked = false;
                    }
                }
            \" value=\"\" /></td><td><strong>All</strong></td>";

            $columns = 1; if (isset($attributes['columns'])) {
                $columns = $attributes['columns'];
            }
            $cnt = 1;
            foreach ($options as $id => $value) {
                if ($cnt % $columns == 0) {
                    $result .= "</tr>\r\n<tr>";
                }
                $result .= "<td><input name =\"".$name."[]\" class=\"checkbox\" type=\"checkbox\" value=\"$id\" ";
                if ($values && in_array($id, $values)) {
                    $result .= "checked=\"checked\" ";
                }
                $result .= 'onclick="if (this.checked == false) {'.$element_name.'_all.checked = false;}"';
                $result .= '/></td><td>'.htmlentities(stripslashes($value)).'</td>';
                $cnt++;
            }
            $result .= '</tr></table>';
            $result .= '<input type="hidden" name="'.$name.'[]" checked="checked" value="-1" />';
            $result .= "</div>";
        }
        return $result;
    }

    function select($name, $value = null, $attributes = null, $options = null)
    {
        /*
         * $options takes a either html source of options or one of these values:
         * yes_no, true_false
         */
        $result = "<select id=\"".$name."\" name=\"$name\"";
        $result .= self::parse_attributes($attributes, array('default'));
        $result .= ">";
        if (!is_array($options) && $options == 'yes_no' )
        {
            $result .= '<option value="Y" ';
            if ( $value == 'Y' | (!$value && $attributes['default'] == 'Y')) { $result .= 'selected = "selected"'; }
            $result .= '>Yes</option>' ;
            $result .= '<option value="N" ';
            if ( $value == 'N' | (!$value && $attributes['default'] == 'N')) { $result .= 'selected = "selected"'; }
            $result .= '>No</option>' ;
        }
        elseif (!is_array($options) && $options == 'true_false' )
        {
            $result .= '<option value="true" ';
            if ( $value == 'true' ) { $result .= 'selected = "selected"'; }
            $result .= '>True</option>' ;
            $result .= '<option value="false" ';
            if ( $value == 'false' ) { $result .= 'selected = "selected"'; }
            $result .= '>False</option>' ;

        }
        elseif (!is_null($options))
        {
                $result .= $options;
        }
        $result .= "</select>";
        return $result;
    }

    function date($name, $value = null, $attributes = null)
    {
        $result = '';

        if ($value && $value != 0) {$value = strftime(DATE_FORMAT, strtotime((string)$value));} else { $value = ''; }
        $result .= '<input readonly="readonly" type="text" name="'.$name.'" value="'.$value.'" ';
        // auto id
            if (!$attributes || ($attributes && !$attributes['id'])) { $id = $name; $result .= 'id="'.$name.'" '; } else {$id = $attributes['id'];}


        $result .= self::parse_attributes( $attributes );
        $result .= ' />';
        $result .= '<input type="button" class="date_picker_button" id="'.$id.'_button" value="..." />';

        $result .= '
            <script type="text/javascript">
            Calendar.setup({
                inputField     :    "'.$id.'",     // id of the input field
                ifFormat       :    "'.DATE_FORMAT.'",      // format of the input field
                button         :    "'.$id.'_button",  // trigger for the calendar (button ID)
                align          :    "Tl",           // alignment (defaults to "Bl")
                singleClick    :    true
            });
        </script>';
    return $result;
    }

    function input($name, $value = null, $attributes = null)
    {
        //attributes includes id, readonly etc.. whatever is in there get's set
        $result = '';
        //default attributes
            // id
                if (!isset($attributes['id'])) { $attributes['id'] = $name; }
            // type
                if (!isset($attributes['type'])) { $attributes['type'] = 'text'; }

                $result .= '<input name="'.$name.'" value="'.stripslashes($value).'" ';

        $result .= self::parse_attributes( $attributes );

        $result .= ' />';
    return $result;
    }

    function textarea($name, $value = null, $attributes = null)
    {
        $result = '';
        if (!isset($attributes['id'])) { $attributes['id'] = str_replace(']', '', str_replace('[', '_', $name)); }

        $result .= '<textarea name="'.$name.'" ';
        $result .= self::parse_attributes( $attributes );
        $result .= ' />'.stripslashes($value).'</textarea>';

    return $result;
    }

    function subheading($name, $value = null, $attributes = null)
    {
      $result .= '<h3';
      $result .= self::parse_attributes( $attributes );
      $result .= '>'.$name.'</h3>';
      return $result;
    }

    function partial($name)
    {
        /* this whole partial handling is a HACK! action_controller should be rendering the partial. not this way. Ugh! */
        $base_url = App::$route;

        if (!is_array($name)) {
            $base_url['action'] = "_$name.php";
        }
        else {
            foreach($name as $url_part => $value) {
                $base_url[$url_part] = $value;
            }
        }

        global $path_to_root;

        $path = ($path_to_root.'/'.
            $base_url['face'].'/'.
            'views/'.
            str_replace('_controller', '', $base_url['controller']).'/'.
            $base_url['action']
            );
        require($path);
    }

    function parse_attributes($attributes, $except = null)
    {
        $result = '';
        if ($attributes) {
            foreach ($attributes as $option => $option_value) {
                if (!$except | ($except && !in_array($option, $except))) {
                    $result .= $option.'="'.$option_value.'" ';
                }
            }
            return $result;
        }
    }

    function form_element($title, $validation_requirements, $element_id, $element, $note = null)
    {
        $result = '';
        $result .= '<p class="form_element" id="fe_'.forms::name_to_id($element_id).'">';
        $result .= '<label>';
        if ($title != '') {
            $result .= $title.':';
        }
        $result .= $validation_requirements;
        $result .= '</label><span>'.$element.'</span>';
        if ($note) { $result .='<span class="note">'.$note.'</span>';}
        $result .= '</p>';
        return $result;
    }

    function form($default_model, $record = null)
    {
        ?><div class="form list_form"><?

        $page = App::$controller;
        $arguments = func_get_args();
        if (sizeof($arguments) == 1) {$arguments = $arguments[0];} //arguments are being passed as an array

        /*
         * arg 0 is the default model
         * arg 1 is the record
         * arg 2... should be arrays for the elements
         */

        $default_model = $arguments[0];
        $record = $arguments[1];
        for ($i=2;$i<sizeof($arguments);$i++)
        {
            $arg = $arguments[$i];
            if (is_array($arg)) // it's a form element
            {
                $draw_element = true;
                //is there an 'only' option ?
                if ( array_key_exists( 'only', $arg ) )
                {
                    $only = $arg['only'];
                    $only = explode( ',', $only );
                    if (!in_array( $page->action, $only ))
                    {
                        $draw_element = false;
                    }
                }

                if ($draw_element) {
                    //is it a partial or a form element ?
                    if ($arg[1] == 'partial') {
                        self::partial($arg[0]);
                    } else {
                        self::draw_element( $arg, $default_model, $record );
                    }
                }
            }
            else
            {
                //spit it out as is
                echo $arg;
            }
        }
        //foreign key(s)
        foreach ($this->foreign_keys as $key => $value) {
            if ($page->primary_model == $default_model) //only make hiddens for FK's when building a form for this page
            {

                $prefix = pluralize($this->primary_model).'.';
                if (substr($key, 0, strlen($prefix)) == $prefix) {
                    $key = substr($key, strlen($prefix));
                }

                $key = "$default_model"."[$key]";
                echo self::input($key, $value, array('type'=>'hidden'));
            }
        }
        ?></div><?
    }

    /*
     * form element definition
     * 0 = display_name
     * 1 = element type e.g. input, select
     * named values are dealt with. everything else is passed on as the attributes array
     *
     * named values:
     *  name
     *  value
     *  options (for select)
     */
    function draw_element($form_field, $default_model, $record)
    {
        $field_name = $db_field_name = null; $visible = true; //some inits
        $page = App::$controller;
        $primary_model_object = new $default_model;

        /* single fields */
        if (sizeof($form_field) == 1) {
          switch ($form_field[0]) {
          case 'active':
            $form_field[1] = 'select';
            $form_field['options'] = 'yes_no';
            break;
          case 'password':
            $db_field_name = 'password_md5';
            $form_field[1] = 'input';
            $field_name = "$default_model"."[$db_field_name]";
            //$form_field['type'] = "password"; not setting to password because it is never shown. plain text entry is better
            $form_field['value'] = '';
            if ( $page->action == 'edit' ) {$form_field['note'] = '(leave blank to leave password unchanged)';}
            break;
          default:
            // input type=text with this as title and field_name
            $form_field[1] = 'input';
            break;
          }
        }

        //convert type of hidden to type input attrib type=hidden
        if ($form_field[1] == 'hidden') {
            $form_field[1] = 'input';
            $form_field['type'] = 'hidden';
            $visible = false;
        }

        // convert a type of "string" to "input=text"
        if (isset($form_field[1]) && $form_field[1] == 'string') { $form_field[1] = 'input'; $form_field['type'] = 'text'; }
        // convert a type of "text" to "textarea"
        if (isset($form_field[1]) && $form_field[1] == 'text') { $form_field[1] = 'textarea'; }

        // upload ? Set the field_name
        if ($form_field[1] == 'upload') {
            /* putting this here prevents it from setting the db_field_name later and trying to find a value for it */
            $field_name = $default_model.'['.$form_field[0].']';
        }
        //upload and edit ? pass upload identifier array as value
        if (isset($form_field[1]) && $form_field[1] == 'upload' && $page->action == 'edit') {
            $form_field['value'] = array(
                'model' => $default_model,
                'field_name' => $form_field[0],
                'record_id' => $record->id
            );
        }
        // subheading
        if ($form_field[1] == 'subheading') {
            /* putting this here prevents it from setting the db_field_name later and trying to find a value for it */
            $field_name = $form_field[0];
            $visible = false; /* a hack to force it to output the html straight */
        }

        /* find the options for the select */
        if (isset($form_field['model'])) {
            $fk_model = $form_field['model'];
        } else {
            $fk_model = strtolower(tableize($form_field[0]));
        }
        if (($form_field[1] == 'select' || $form_field[1] == 'multi_select')) {
          if (!isset($record->$fk_model)) {  /* this checks whether or not the field_name is a property or not. If it is a property then it skips this section */
            if ($form_field['field']) { $field = $form_field['field'];} else {$field = null;}
            if ($form_field['show_all_option']) {$show_all_option = $form_field['show_all_option'];} else { $show_all_option = null; }
            if (isset($form_field['criteria'])) { $criteria = $form_field['criteria']; } else { $criteria = 'all'; }
            if (isset($form_field['additional_sql_options'])) { $additional_sql_options = $form_field['additional_sql_options']; } else { $additional_sql_options = null; }
            if ($form_field['order by']) { $additional_sql_options['ORDER BY'] = $form_field['order by']; }

            $db_field_name = foreign_keyize(strtolower($form_field[0])); //todo. this should only foreign_keyize IF model is not set.. todo: why? (06/feb/2008)

            //debug($fk_model);debug($db_field_name);

            /* select */
            switch ($form_field[1]) {
            case 'select':
                if (class_exists($fk_model)) {
                    $options_object = new $fk_model;
                } else {
                    trigger_error("<i>$fk_model</i> model class does not exist.", E_USER_ERROR);
                }

                if (!($primary_model_object->has_one($fk_model) || $primary_model_object->belongs_to($fk_model))) {
                    trigger_error("Relationship to  <i>".$fk_model."</i> not found", E_USER_ERROR);
                }

                $field_name = $default_model."[$db_field_name]";

                $options = $options_object
                    ->find($criteria, $additional_sql_options)
                    ->as_select_options($record->$db_field_name, $field, $show_all_option);
                $form_field['options'] = $options;
                break;

            /* multi select */
            /* NOTE: fk_model comes through already pluralized for multi-selects. It makes sense that way */
            case 'multi_select':
                if( $primary_model_object->has_many_through($fk_model) ) {
                    //multi select
                    $options_object = singularize($fk_model);
                    $options_object = new $options_object;

                    $join_model_object_name = singularize($primary_model_object->has_many_through($fk_model));
                    $join_model_object = $join_model_object_name;
                    $join_model_object = new $join_model_object;

                    $db_field_name = foreign_keyize(singularize($fk_model));

                    $field_name = $join_model_object_name."[$db_field_name]";

                    /* show the parent for acts_as_nested_set objects */
                    if (!$options_object->acts_as_nested_set) {
                        $options = $options_object
                            ->find($criteria, $additional_sql_options)
                            ->as_array($field);
                    } else {
                        $options = $options_object
                            ->find($criteria, $additional_sql_options)
                            ->as_array($field);
                    }
                    $finder_name = 'find_by_'.foreign_keyize($default_model);//".$record->id."'";
                    $values =  $join_model_object->$finder_name($record->id)->as_array(foreign_keyize(singularize($fk_model))); //get the values from the db with a primary_model->as_array;

                    $form_field[0] = $form_field[0];
                    $form_field['options'] = $options;
                    $form_field['value'] = array_values($values);

                } elseif (!(array_key_exists('options', $form_field))) {
                    trigger_error("Relationship to  <i>".$fk_model."</i> not found", E_USER_WARNING);
                }
                break;
            }
          } elseif (!array_key_exists('options', $form_field)) {
            trigger_error("Trying to draw a select for the <i>$fk_model</i> property but no options have been supplied. Is this a misconfigured <i>has_one</i> relationship?", E_USER_ERROR);
          }
        }

        //field_name and db_field_name
        if (!$field_name && !$db_field_name) { // if not set by a special case
            if (array_key_exists('name', $form_field)) {
                $field_name = $form_field['name'];
                //pull the db_field_name out
                $bpos = strpos($field_name, '[');
                if ($bpos > 0) {
                    $db_field_name = substr($db_field_name, $bpos+1, -1);
                } else {
                    $db_field_name = $field_name;
                }
            } else {
                $db_field_name = strtolower(tableize($form_field[0])); // I'm strtolowering because I'm going to assume that if you don't specificy a specific name for the field then it should be all lowered automatically
                $field_name = $default_model.'['.$db_field_name.']';
            }
        }
        //value
        if (array_key_exists('value', $form_field)) {
          $value = $form_field['value'];
        } else {
          /* no value set for this field but a db_field_name is set */
          if ($db_field_name) {
              $value = htmlentities($record->$db_field_name);
          }
        }

        if (array_key_exists('options', $form_field)) {
          $options = $form_field['options'];
        } else {
          $options = null;
        }

        //convert form_field to attributes, by removing all the non-attribute stuff
        $attributes = $form_field;
        foreach(array(0, 1, 2, 3, 'name', 'options', 'value', 'note', 'only', 'show_all_option', 'order_by','criteria', 'field', 'model', 'label', 'additional_sql_options') as $key) {
          unset($attributes[$key]);
        }

        $element_function = $form_field[1];
        $element_html = self::$element_function($field_name, $value, $attributes, $options);

        //if this is a visible element then draw it inside a labelled container, else just draw the element (generally a hidden)
        if ($visible) {
            //determine the validation requirements for this field
            $model_object = new $default_model;

            if (isset($form_field['label'])) {
                $label = $form_field['label'];
            } else {
                $label = humanize($form_field[0]);
            }
            if (!isset($form_field['note'])) {$form_field['note'] = '';}
            echo self::form_element($label, $model_object->requirements($db_field_name), $field_name, $element_html, $form_field['note']);
        }
        else
        {
            //e.g. a hidden
            echo $element_html;
        }
    }

    function form_buttons($update_name = "update", $show_reset = true, $show_cancel = true, $show_note = true)
    {
        ?><input type="submit" value="<?=$update_name?>" />&nbsp;
        <? if ($show_reset) {?><input type="reset" value="reset" />&nbsp;<? } ?>
        <? if ($show_cancel) {?><input type="button" value="cancel" onclick="window.location='<?=redirect_with_parameters(url_to(array('action' => 'list')), '', true);?>'" />&nbsp;<? } ?><br />
        <br /><strong>Note:</strong> All fields marked with <strong>*</strong> are required.
<?
    }

    function button($name, $value = 'unused', $attributes = null)
    {
      //attributes includes id, readonly etc.. whatever is in there get's set
      $result = '';
      //default attributes
      // id
      if (!isset($attributes['id'])) { $attributes['id'] = $name; }
      // type
      if (!isset($attributes['type'])) { $attributes['type'] = 'text'; }


      $result .= '<button ';
      $result .= self::parse_attributes( $attributes );

      $result .= '>'.stripslashes($name).'</button>';
    return $result;
    }
}
?>
