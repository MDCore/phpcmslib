<?
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
        # 
        if ( $page->action == 'edit' )
        {
            #preview this sucker
            $upl = new upload;
            $upl->load($value['model'], $value['field_name'], $value['record_id']);
            if ($upl->file_exists())
            {
                $result .= '<a href="'.$upl->display_filename().'">View current '.humanize($value['field_name']).'</a><br />';

            }
            $result .="<input onchange=\"new_attachment(this);\" id=\"".$name."\" type=\"file\" name=\"$name\"";
            $result .= self::parse_attributes( $attributes );
            $result .=" />";
        }
        else
        {
            #
            $result .="<input onchange=\"new_attachment(this);\" id=\"".$name."\" type=\"file\" name=\"$name\"";
            $result .= self::parse_attributes( $attributes );
            $result .=" />";
        }
        $result .= "<div>Note: You cannot upload individual files larger than ".ini_get('upload_max_filesize'). " and the entire upload will fail if larger than ".ini_get('post_max_size').".</div>";
    return $result;
    }

    function multi_select( $name, $values = null, $attributes = null, $options = null )
    {
        $result = '';
        if (sizeof($options)> 0 )
        {
            $element_name = $name; $element_name = str_replace( '[', '_', $element_name); $element_name = str_replace( ']', '_', $element_name);
            $result .= "<div id=\"".$element_name."_container\">"; 
            $result .= "<input name =\"".$name."[]\" class=\"checkbox\" type=\"checkbox\" id=\"".$element_name."_all\" onclick=\"
                var f = $('div#".$element_name."_container input[@type=checkbox]');
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
                \" value=\"\" /> <strong>All</strong> <br /> ";

            foreach ($options as $id => $value)
            {
                $result .= "<input name =\"".$name."[]\" class=\"checkbox\" type=\"checkbox\" value=\"$id\" ";
                if ($values && in_array($id, $values)) {$result .= "checked=\"checked\"";}
                $result .=" onclick=\"if (this.checked == false) {".$element_name."_all.checked = false;}\"";
                $result .="/> $value <br />  ";
            }
            $result .="<input type=\"hidden\" name=\"".$name."[]\" checked=\"checked\" value=\"-1\" />";
            $result .= "</div>";
        }
        return $result;
    }

    function select( $name, $value = null, $attributes = null, $options = null )
    {
        /*
         * $options takes a either html source of options or one of these values:
         * yes_no, true_false 
         */
        $result = "<select id=\"".$name."\" name=\"$name\"";
        $result .= self::parse_attributes($attributes);
        $result .= ">"; 
        if (!is_array($options) && $options == 'yes_no' )
        {
            $result .= '<option value="Y" ';
            if ( $value == 'Y' ) { $result .= 'selected = "selected"'; }
            $result .= '>Yes</option>' ;
            $result .= '<option value="N" ';
            if ( $value == 'N' ) { $result .= 'selected = "selected"'; }
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
        # auto id
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

    function input($name, $value = null, $attributes = null) {
        #attributes includes id, readonly etc.. whatever is in there get's set
        $result = '';
        #default attributes
            # id
                if (!isset($attributes['id'])) { $attributes['id'] = $name; }
            # type
                if (!isset($attributes['type'])) { $attributes['type'] = 'text'; }

                $result .= '<input name="'.$name.'" value="'.stripslashes($value).'" ';

        $result .= self::parse_attributes( $attributes );

        $result .= ' />';  
    return $result;
    }

    function textarea($name, $value = null, $attributes = null) {
        #attributes includes id, readonly etc.. whatever is in there get's set
        $result = '';
        #default attributes
            # id
                if (!isset($attributes['id'])) { $attributes['id'] = 'name'; }

                $result .= '<textarea id="'.$name.'" name="'.$name.'" ';

        $result .= self::parse_attributes( $attributes );

        $result .= ' />'.stripslashes($value).'</textarea>'; 
    return $result;
    }

    function partial($name) {
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

    function parse_attributes( $attributes ) {
        $result = '';
        if ($attributes) {
            foreach ($attributes as $option => $option_value)
            {
                $result .= $option.'="'.$option_value.'" ';
            }
            return $result;
        }
    }

    function form_element($title, $validation_requirements, $element_id, $element, $note = null)
    {
        $result = '';
        $result .= '<p class="form_element" id="fe_'.forms::name_to_id($element_id).'">';
        $result .= '<label>'.$title.':';
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
        if (sizeof($arguments) == 1) {$arguments = $arguments[0];} #arguments are being passed as an array 

        # arg 0 is the default model
        # arg 1 is the record
        # arg 2... should be arrays for the elements
            
        $default_model = $arguments[0];
        $record = $arguments[1];
        for ($i=2;$i<sizeof($arguments);$i++)
        {
            $arg = $arguments[$i];
            if (is_array($arg)) # it's a form element
            {
                $draw_element = true; 
                #is there an 'only' option ?
                if ( array_key_exists( 'only', $arg ) )
                {
                    $only = $arg['only'];    
                    $only = split( ',', $only );
                    if (!in_array( $page->action, $only ))
                    {
                        $draw_element = false;
                    }
                }

                if ( $draw_element ) 
                {
                    #is it a partial or a form element ?
                    if ( array_key_exists( 'partial', $arg ) )
                    {
                        self::partial($arg['partial']);
                    }
                    else
                    {
                        self::draw_element( $arg, $default_model, $record );
                    }
                }
            }                
            else
            {
                #spit it out as is
                echo $arg;
            }
        }
        #foreign key(s)
        foreach ($this->foreign_keys as $key => $value) {
            if ($page->primary_model == $default_model) #only make hiddens for FK's when building a form for this page
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

    function draw_element( $element_description, $default_model, $record )
    {
        #todo this method badly needs some refactoring
        
        # form element definition
        # 0 = display_name
        # 1 = element type e.g. input, select
        # named values are dealt with. everything else is passed on as the attributes array
        #
        # named values:
        #   name
        #   value
        #   options ( for select )

        $field_name = $db_field_name = null; $visible = true; #some inits
        $page = App::$controller;
        $primary_model_object = new $default_model;
        #print_r($element_description);
        if ( sizeof( $element_description ) == 1 || !isset($element_description[1]))
        {
            #default settings:
            # input type=text with this as title and field_name
            $element_description[1] = 'input'; 

            #check for other special cases
            if ($element_description[0]  == 'password')
            {
                $db_field_name = 'password_md5'; 
                $field_name = "$default_model"."[$db_field_name]";
                #$element_description['type'] = "password"; not setting to password ebcause it is never shown. plain text entry is better
                $element_description['value'] = '';
                if ( $page->action == 'edit' ) {$element_description['note'] = '(leave blank to leave password unchanged)';}
            }
        }
        #convert type of hidden to type input attrib type=hidden
        if ( $element_description[1] == 'hidden' )
        {
            $element_description[1] = 'input'; 
            $element_description['type'] = 'hidden';
            $visible = false;
        }
        # convert a type of "strign" to "input=text"
        if (isset($element_description[1]) && $element_description[1] == 'string') {$element_description[1] = 'input'; $element_description['type'] = 'text';}
        # convert a type of "text" to "textarea"
        if (isset($element_description[1]) && $element_description[1] == 'text') {$element_description[1] = 'textarea';}

        #upload and edit ? pass upload identifier array as value
        if (isset($element_description[1]) && $element_description[1] == 'upload' && $page->action == 'edit')
        {
            $element_description['value'] = array(
                'model' => $default_model,
                'field_name' => $element_description[0],
                'record_id' => $record->id
            );
        }

        #a select with no options and no matching field in the record and a has_one or has_many_through in the model
        if (isset($element_description['model'])) {$fk_model = $element_description['model'];} else {$fk_model = strtolower(tableize($element_description[0]));}
        if ( ($element_description[1] == 'select' || $element_description[1] == 'multi_select') && !isset($record->$fk_model) ) {

            if ($element_description['field']) {$field = $element_description['field'];} else {$field = null;}
            if ($element_description['show_all_option']) {$show_all_option = $element_description['show_all_option'];} else { $show_all_option = null; }
            if (isset($element_description['criteria'])) { $criteria = $element_description['criteria']; } else { $criteria = 'all'; }
            if (isset($element_description['additional_sql_options'])) { $additional_sql_options = $element_description['additional_sql_options']; } else { $additional_sql_options = null; }
            if ($element_description['order by']) { $additional_sql_options['ORDER BY'] = $element_description['order by']; }

            $db_field_name = foreign_keyize(strtolower($element_description[0])); #todo. this should only foreign_keyize IF model is not set.. todo: why? (06/feb/2008)

            #debug($fk_model);debug($db_field_name);

            /* select */
            switch ($element_description[1]) {
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

                $options = $options_object->find($criteria, $additional_sql_options)->as_select_options($record->$db_field_name, $field, $show_all_option);
                $element_description['options'] = $options;
                break;

            /* multi select */
            case 'multi_select':
                if( $primary_model_object->has_many_through(pluralize($fk_model)) ) {
                    #multi select
                    $options_object = new $fk_model;

                    $join_model_object_name = $primary_model_object->has_many_through(pluralize($fk_model));
                    $join_model_object = new $join_model_object_name;
                    
                    $db_field_name = foreign_keyize($fk_model);

                    $field_name = $join_model_object_name."[$db_field_name]";

                    $options = $options_object->find($criteria, $additional_sql_options)->as_array($field);
                    $value_criteria = "WHERE ".foreign_keyize($default_model). " = '".$record->id."'";
                    $values =  $join_model_object->find($value_criteria)->as_array(foreign_keyize($fk_model)); #get the values from the db with a primary_model->as_array;

                    $element_description[0] = pluralize($element_description[0]); //todo why ?
                    $element_description['options'] = $options;
                    $element_description['value'] = array_values($values);
                    
                } elseif (!(array_key_exists('options', $element_description))) {
                    trigger_error("Relationship to  <i>".$fk_model."</i> not found", E_USER_WARNING); 
                }
                break;
            }
        }
        
        #field_name and db_field_name
        if (!$field_name && !$db_field_name) # if not set by a special case
        {
            if (array_key_exists('name', $element_description))
            {
                $field_name = $element_description['name'];
                #pull the model and db_field_name out
                $bpos = strpos($field_name, '[');
                if ($bpos > 0)
                {
                    $field_model = substr($field_name, 0, $bpos-1);
                    $db_field_name = substr($db_field_name, $bpos+1, -1);
                }
                else
                {
                    $db_field_name = $field_name;
                    $field_model = $default_model;
                }
            }
            else
            {
                $db_field_name = strtolower(tableize($element_description[0])); # I'm strtolowering because I'm going to assume that if you don't specificy a specific name for the field then it should be all lowered automatically

                $field_name = $default_model.'['.$db_field_name.']';
                $field_model = $default_model;
            }
        }
        #value
            if (array_key_exists('value', $element_description))
            {
                $value = $element_description['value'];
            }
            else
            {
                #if (($page->action == 'edit') && $db_field_name)
                if ($db_field_name)
                {
                    $value = $record->$db_field_name;     
                }
            }

        if (array_key_exists('options', $element_description))
        {
            $options = $element_description['options'];
        }
        else
        {
            $options = null;
        }

        #convert element_description to attributes, by removing all the non-attribute stuff
            $attributes = $element_description;
            foreach(array(0, 1, 'name', 'options', 'value', 'note', 'only', 'show_all_option', 'order_by','criteria', 'field', 'model', 'label') as $key){unset($attributes[$key]);}

        $element_function = $element_description[1];
        $element_html = self::$element_function($field_name, $value, $attributes, $options);

        #if this is a visible element then draw it inside a labelled container, else just draw the element (generally a hidden)
        if ( $visible )
        {
            #determine the validation requirements for this field
            $model_object = new $default_model;

            if (isset($element_description['label'])) {
                $label = $element_description['label'];
            } else {
                $label = humanize($element_description[0]);
            }
            if (!isset($element_description['note'])) {$element_description['note'] = '';}
            echo self::form_element($label, $model_object->requirements($db_field_name), $field_name, $element_html, $element_description['note']);
        }
        else
        { #e.g. a hidden
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
}
?>
