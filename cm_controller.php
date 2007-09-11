<?
class cm_controller extends action_controller
{
    public $before_controller_load_filter = 'is_logged_in';
    public $before_controller_execute_filter = 'check_for_print';

    public $is_controller = true;
    public $layout = 'default';
    public $face = 'cm';
    public $default_action = 'cm_list';

    #default configuration 
        public $allow_edit = true, $allow_add = true, $allow_delete = true;
        public $allow_filters = true, $allow_sort = true;
        public $row_limit = 30;

    public $list_sort_field = null, $list_sort_type = null;

    function __construct()
    {
        parent::__construct();

        #the face_controller should be virtual;
            if ($this->controller_name == 'face') {$this->virtual = true;}
        if ($this->virtual) { return true; }

        # todo is also to document this all!
            #foreign key(s)
                if (isset($_GET['fk'])) {
                    $fk = $_GET['fk'];
                    $fk = explode(',', $fk);
                    foreach ($fk as $keyval)
                    {
                        $key = substr($keyval, 0, strpos($keyval, '~'));
                        $value = substr($keyval, strpos($keyval, '~')+1);
                        $this->foreign_keys[$key] = $value;
                    }
                }
                else
                {
                    $this->foreign_keys = array();
                }
       
            if (!isset($this->list_type))       { $this->list_type =  singularize($this->controller_name); }
            if (!isset($this->primary_model))   { $this->primary_model = $this->list_type; }
            if (!isset($this->list_title))      { $this->list_title = proper_nounize(pluralize($this->list_type)); } $this->list_title = proper_nounize($this->list_title);
            if (!isset($this->email_subject))   { $this->email_subject = $this->list_title; }

            #edit page title
                $this->edit_page_title = "Editing a";
                switch(strtolower(substr($this->list_type, 0, 1))) {case 'a': case 'e': case 'i': case 'o': case 'u': $this->edit_page_title .= 'n';}
                $this->edit_page_title .= ' '.humanize($this->list_type);
             
            $this->draw_form_buttons = true;

            if (isset($_GET['delete']) && $_GET['delete'] == 'y') {$this->show_delete = true;} else {$this->show_delete = false;}

        #setup some objects
            $this->model_object = new $this->primary_model; #instantiate an object of this model so we can interrogate it
            $this->filter_object = new filter;
            
        #pull certain variables from the model
            $this->primary_key_field = $this->model_object->primary_key_field;
            $this->schema_table = $this->model_object->schema_table;
            
        # setup the SQL query for the list page
            $sql_pk = $this->schema_table.".".$this->primary_key_field." as __pk_field";
            if (!isset($this->sql_query))
            {
                $this->sql_query = "SELECT ".$this->schema_table.".*, $sql_pk FROM ".$this->schema_table;
            }
            else
            {
                #oh... um.. right then... well... ok.
            }

        if ( isset( $this->view_page ) )
        {
            $this->allow_view = true;
        }
        else
        {
            $this->view_page = tableize(pluralize($this->list_type)).'_view.php';
        }

        if ($this->allow_filters)
        {
            if ($this->filters) {
                #setup the filter names
                $this->filter_object->init($this->primary_model, $this->filters);
                
                $this->has_filters = true;
            }
            else
            {
                $this->has_filters = false;
            }
        }

        #some fk stuff
            if (!isset($this->foreign_key_title_prefix)) {$this->foreign_key_title_prefix = ' in ';}

        #changes for print mode
            if (defined('PRINTING_MODE'))
            {
                $this->allow_filters = false;
                $this->allow_add = false;
                $this->allow_edit = false;
                $this->allow_view = false;
                $this->allow_delete = false;
                $this->draw_form_buttons = false;
                $this->row_limit = 1000000;
                $this->allow_sort = false;
            }
    }

    function __call($method_name, $params)
    {
        $this->cm_page($method_name);
    }

    function cm_page($page) 
    {
        global $path_to_root;

        $this->action = $page;
        #automatic cm pages router
            switch ($page)
            {
            case 'list':
                if (method_exists('_list', $this)) { $this->_list(); } else { $this->cm_list(); }
                #require the additional scripts #todo remove this.. possibly redundant with controllers / actions
                    if (isset($this->additional_scripts))
                    {
                        foreach ($this->additional_scripts as $script)
                        {
                            require($path_to_root.'/cm/'.$script);
                        }
                    }
                break;
            case 'edit':
                $this->cm_edit();break;
            case 'add':
                $this->cm_add();break;
            case 'view':
                $this->cm_view();break; 
            case 'save':
                $this->cm_save();break;
            case 'update':
                $this->cm_update();break;
            case 'delete':
                $this->cm_delete();break;
            default:
                $this->action = null;
            }
    }
    
    function related_page_anchor($related_page, $row)
    {
        if (!isset($related_page['controller'])) {$related_page['controller'] = $related_page[0];}
        #if (!isset($related_page['fk'])) {$related_page['fk'] = foreign_keyize($this->list_type);}
        #if (!isset($related_page['fk_title_field'])) {$related_page['fk_title_field'] = $this->model_object->display_field;}
        
        $target = array(
            'controller' => $related_page['controller'],
            'action'     => $related_page['action']
        );
        if (isset($related_page['id'])) { $target['id'] = $row->{$related_page['id']}; }
        ?><td class="action_link"><a href="<?echo url_to($target);

        if (isset($related_page['fk']) || (isset($related_page['fk_title_field']))) { echo '?p=y'; }
        if (isset($related_page['fk'])) { ?>&amp;fk=<?=$related_page['fk'];?>~<? echo $row->id; }
        if (isset($related_page['fk_title_field'])) { ?>&amp;fk_t=<? echo urlencode($row->{$related_page['fk_title_field']}); }

        ?>"><?

        if (isset($related_page['title'])) { echo $related_page['title']; } else echo htmlentities(proper_nounize($related_page['controller']));

        ?></a></td><?

    }

#------------------------------#
# Action Presets
#------------------------------#

    public function cm_update()
    {
        $edit_id = $_GET['edit_id'];

        #print_r($_GET);print_r($_POST);print_r($_FILES);
        if (isset($_POST[$this->primary_model]))
        {
            $primary_model_object = new $this->primary_model;
            $primary_model_object->find($edit_id);
            $primary_model_object->update_attributes($_POST[$this->primary_model]);
            $successful = $primary_model_object->update();
        }
        else
        {
            $no_primary_to_save = true;
        }

        if ($successful | $no_primary_to_save)
        {
            unset($_POST['MAX_FILE_SIZE']); #currently used to add a comment each time
            foreach ( $_POST as $meta_model => $collection )
            {
                if ( $meta_model != $this->primary_model ) # make sure we are working with meta models
                {
                    $fk_field = foreign_keyize($this->primary_model);
                    $collection[$fk_field] = $edit_id; #add the foreign key straight into the collection

                    if (!isset($collection['_add_record']))
                    {
                        if ($primary_model_object->through_model($meta_model))
                        {
                            $meta_model_object = new $meta_model;
                            $meta_model_object->delete("WHERE $fk_field = $edit_id"); #delete the records, to re-add them
                            $meta_model_object->save_multiple($collection);
                        }
                        else
                        {
                            $meta_model_object = new $meta_model; 
                            $meta_model_object->find(" WHERE $fk_field = $edit_id");
                            $meta_model_object->update_attributes($collection);
                            if (!$meta_model_object->is_valid())
                            {
                                redirect_with_parameters(url_to(array('action' => 'edit')), "edit_id=".$edit_id."&flash=".$meta_model_object->validation_errors);die();
                            }
                            $meta_model_object->update();
                        }
                    }
                    {
                        unset($collection['_add_record']); #currently used to add a comment each time
                        #todo duplicate the meta-model code from cm_save
                        $meta_model = new $meta_model($collection); $meta_model->save();
                    }
                }
            }
            $this->handle_new_files($edit_id, true);

            redirect_with_parameters(url_to(array('action' => 'list')), "flash=".proper_nounize($this->list_type). " updated");
        }
        else
        {
            redirect_with_parameters(url_to(array('action' => 'edit')), "edit_id=".$edit_id."&flash=".$primary_model_object->validation_errors);
        }
    }

    public function cm_save()
    {
        #debug('handling_save');print_r($_GET);print_r($_POST);print_r($_FILES);
        
        if (function_exists('before_save')) { before_save(); } #todo clean this up.... should be in model, maybe
        
        $collection = $_POST[$this->primary_model];
        if ( !$collection ) {
            $collection = $_POST;
            $has_meta_data = false;
        }
        else
        {
            $has_meta_data = true;
        }
        # save the form data for the primary model 
            $primary_model_object = new $this->primary_model($collection);

        if (!$primary_model_object->is_valid()) {
            $_GET['flash'] = $primary_model_object->validation_errors; 
            $this->cm_add();
            return false;
        }

        $primary_record_id = $primary_model_object->save(); 

        if ($primary_record_id) #might not have one if saving failed e.g. validation
        {
            if ($has_meta_data)
            {
                #deal with related tables
                foreach ( $_POST as $meta_model => $collection )
                {
                    if ( $meta_model != $this->primary_model)
                    {
                        $collection[foreign_keyize($this->primary_model)] = $primary_record_id; #add the foreign key straight into the collection

                        if ($primary_model_object->through_model($meta_model))
                        {
                            $meta_model_object = new $meta_model;
                            #$meta_model_object->delete("WHERE $fk_field = $edit_id"); #delete the records, to re-add them
                            $meta_model_object->save_multiple($collection);
                        }
                        else
                        {
                            $meta_model_object = new $meta_model($collection); 
                            if (!$meta_model_object->is_valid())
                            {
                                #delete the primary_record
                                $primary_model_object->delete($primary_record_id);
                                if (!$meta_model_object->is_valid()) { redirect_with_parameters(url_to(array('action' => 'list')), "flash=".$meta_model_object->validation_errors); die(); }
                            }
                            $meta_model->save(); #don't need the primary id, afaik
                        }
                    }
                }
            }
            $this->handle_new_files($primary_record_id, true);
            # callback here
            if (function_exists('after_save')) { after_save($primary_record_id); } # todo should be in model maybe
            redirect_with_parameters(url_to(array('action' => 'list')), "flash=New ".proper_nounize($this->list_type). " added");
        }
        else
        {
            die("nothing to save");
        }
    }

    public function cm_delete()
    {
        # delete these puppies
            $sql_delete = "WHERE ".$this->primary_key_field." IN (";
            $records_deleted = 0;
            foreach ($_POST['delete'] as $delete_id)
            {
                if (is_int((int)$delete_id))
                {
                    $sql_delete .= $delete_id .',';
                    $records_deleted += 1;
                }
            }
            if (substr($sql_delete, -1, 1) == ',') { $sql_delete = substr($sql_delete, 0, -1); }
            $sql_delete .= ");";
    
        # delete records
            $ign = $this->model_object->delete($sql_delete);

        #set message
            if ($records_deleted != 1) {$flash = proper_nounize(pluralize($this->list_type));} else {$flash = proper_nounize($this->list_type);}
            $flash = "Deleted $records_deleted ".$flash;

        redirect_with_parameters(url_to(array('action' => 'list')), "flash=$flash");
    }

    public function handle_new_files($primary_record_id, $force_new = false)
    {   
        #print_r($_GET);print_r($_POST);print_r($_FILES);
        #file uploads #todo get this working for meta_models
        foreach ($_FILES as $model => $model_files)
        {
            $upload = new upload; 
            foreach ($model_files['name'] as $field_name => $file)
            {
                $upload->load($model, $field_name, $primary_record_id, $force_new);
                if ($upload->file_uploaded()) {$upload->save();}
            }
        }
    }

#------------------------------#
# CM views
#------------------------------#

    public function cm_list()
    {
        if (isset($this->page_title)) { $page_title = $this->page_title; }

        # the list title
        if (isset($_GET['fk_t'])) {$fk_t= $_GET['fk_t']; $this->foreign_key_title = $this->foreign_key_title_prefix.$fk_t; } else {$this->foreign_key_title = '';}
        ?> <h2><?=$this->list_title;?><?=stripslashes($this->foreign_key_title);?></h2><?

        #setup the list fields
            $this->list_fields = split_aliased_string($this->list_fields);

        #setup paging
            if (isset($_GET['start'])) 
            {
                $this->start_limit = $_GET['start'];
            }
            else
            {
                $this->start_limit = 0;
            }
            $this->paging_back = $this->start_limit - $this->row_limit;
            $this->paging_next = $this->start_limit + $this->row_limit;

            if ($this->allow_filters && $this->has_filters)
            {
                echo $this->draw_filters($this->filter_object->filters);
            }
        if ($this->show_delete) { ?><form id="list_delete" method="post" action="<?=href_to(array('action' =>'delete')).page_parameters('', false)?>"><? }

    #---------query, sql_query, sql query ---------------------------------------#
        
        if (!is_array($this->sql_query)) {$list_sql = SQL_explode($this->sql_query);} else { $list_sql = $this->sql_query; }
        

        #if (!isset($list_sql['WHERE']) || $list_sql['WHERE'] == '') { $list_sql['WHERE'] = '1=1'; }
        foreach ($this->foreign_keys as $key => $value) {
            $list_sql['WHERE'][] = " AND $key='$value'";
        }
        
        $filter_sql = $this->filter_object->sql_criteria();

        if (!($filter_sql == false))
        {
            #join from's
                if ($filter_sql['FROM']) { foreach ($filter_sql['FROM'] as $filter_from) { $list_sql['FROM'] .= ' ' . $filter_from; }}

            #join where's
            foreach ($filter_sql['WHERE'] as $filter_where) { $list_sql['WHERE'][] = ' AND '. $filter_where; }
        }

        #sorting
        if (isset($_GET['sort']))
        {
            $sort = $_GET['sort'];
            #asc or desc
            if (substr($sort, -3) == 'asc')
            {
                $sort_type = 'ASC';
            }
            else
            {
                $sort_type = 'DESC';
            }
            $sort = substr($sort, 0, strlen($sort) - strlen($sort_type)-1);
            $this->list_sort_field = $sort;
            $this->list_sort_type = $sort_type;
        }
        if (!$this->list_sort_field && in_array('sort_order', $this->list_fields)) 
        {
            #default #todo put this into some defaults script
            #todo add to magic_stuff documentation
            $this->list_sort_field = "sort_order ASC";
        }
        if ($this->list_sort_field)
        {
            $list_sql['ORDER BY'][] = $this->list_sort_field.' '.$this->list_sort_type;
        }
        
        #turn the array into a string

        #print_r ( $list_sql );
            $results_query = SQL_implode($list_sql);
            $sql_pk = $this->schema_table.".".$this->primary_key_field." as __pk_field";
            $results_query = str_replace( '__pk__', $sql_pk, $results_query );

        if ($this->debug_sql) { debug ( $results_query ); }
        $AR = new AR;
        $results_list = $AR->db->query($results_query." limit ". $this->start_limit.', '.$this->row_limit);
        #error check
        AR::error_check($results_list);

        $sum_query = $results_query;
        $result2 = $AR->db->query($sum_query); AR::error_check($results2); #SQL Query list_query
        $num_rows = $result2->numRows(); #count
        ?><div class="list_rows"><?=$num_rows; ?> <?
        if ($num_rows != 1) {echo humanize(pluralize($this->list_type));} else {echo humanize($this->list_type);}
        
        echo $this->filter_object->match_text($num_rows);        
        ?></div> <?
        ?><div class="list_wrapper"><?
            ?><table class="list"> <?=$this->list_header() ?><?=$this->list_body($results_list);?></table><?
            ?></div><div class="paging"><?=$this->list_paging($num_rows);?></div><?  
        ?><div><?
        if ($this->show_delete) { ?><input type="submit" value="Delete selected" onclick="return confirm('Are you sure you want to delete these <?=humanize(pluralize($this->list_type))?> ?');">&nbsp;<? } 
        if (isset($this->return_page )) {$return_page = $this->return_page ;} else { $return_page = pluralize($this->list_type); }  #XXX
        ?> </div>
        <? if ($this->allow_add || $this->allow_delete) {
            ?><div class="action_links"><? #todo document action_links
                if ($this->back_link) { ?><a href="<?=href_to($this->back_link).page_parameters('/^fk/', false);?>"/>Back to <?=humanize($this->back_link);?></a><br /><? }
            if ($this->allow_add) { ?><a href="<?=href_to(array('action' =>'add')).page_parameters('', false);?>" />Add a new <?=humanize($this->list_type);?></a><br /><? }
            if ($this->allow_delete) { ?><a href="<?=page_parameters('');?>&amp;delete=y"/>Delete <?=humanize(pluralize($this->list_type));?></a><br /><? }
            ?></div><?
        }
        $this->render_inline();
    }

    public function list_header()
    {
        ?><thead><tr><?
        if ($this->show_delete) {?><td><input type="checkbox" id="delete_all" name="delete_all" onclick="select_all_rows();" value="on" /></td><?}
        if (!($this->show_delete) && $this->allow_edit) { ?><th>&nbsp;</th><? }
        if (!isset($this->show_delete) || !($this->show_delete) && $this->allow_view) { ?><th>&nbsp;</th><? }
        if ($this->related_pages && sizeof($this->related_pages) > 0)
        {
            foreach ($this->related_pages as $related_page) { ?><th>&nbsp;</th><? } 
        }
        
        foreach ($this->list_fields as $header => $alias) {
            ?><th <?
            
            if ($this->list_sort_field == $header)
            {
                echo 'class = "sorted"';
            }
            ?>><?
            $sortable = $this->allow_sort;
            if (substr($header, -2) == '()') {
                $sortable = false; 
                
                if (substr($alias, -2) == '()') { $alias = substr($alias, 0, strlen($alias)-2); }
            }
            if ($sortable) {
                ?><a href="<?=page_parameters('/^sort$/')?>&amp;sort=<?=$header;?>_<?
                if ($this->list_sort_field == $header)
                {
                    if ($this->list_sort_type == 'ASC') { echo 'desc'; } else { echo 'asc'; }
                }
                else { echo 'asc'; }
                ?>"><?
            }
            echo proper_nounize($alias);
            if ($sortable) {?></a><?}
            ?></th><?
        }    
        ?> </tr></thead><?
            
    }

    public function list_body($results_list)
    {
        while ($row = $results_list->fetchRow())
        {
            ?><tr class="odd"> <?
            if ($this->show_delete) {?><td><input type="checkbox" class="delete_row" name="delete[]" value="<?=$row->__pk_field;?>" /></td><?}

            if (!$this->show_delete && $this->allow_edit)
            {
            # get or set the edit_link_title
                if (isset($this->edit_link_title)) {$edit_link_title = $this->edit_link_title;} else {$edit_link_title = 'Edit';}#.humanize($this->list_type)

                    ?><td class="action_link"><a href="<?=href_to(array('action' => 'edit')).page_parameters('/^edit/');?>&amp;edit_id=<?=$row->__pk_field;?>"><?=$edit_link_title;?></a></td><?
            }

            if (!$this->show_delete && $this->allow_view)
            {
            # get or set the view_title
                if (isset($this->view_title)) {$view_title = $this->view_title;} else {$view_title = 'View';} #.humanize($this->list_type)

                ?><td class="action_link"><a href="<?=href_to(array('action' => 'view')).page_parameters('/^view/');?>&amp;view_id=<?=$row->__pk_field;?>"><?=$view_title;?></a></td><?
            }

            #related pages
            /* a related page draws an extra action link in the list next to say, edit or delete like so:
            *     [ edit ] [ delete ] [ related ]
            *
            *     This is the structure of a related page:
            *           It is an array of arrays: each related page is a record in the primary array.
            *           A single record is structured like so:
            *                   title                   : the title of the related page. e.g. "related" would be the title in the example above. If this is not set then the target controller name will be used.
            *                   controller              : the target route controller, without _controller appended. E.g. Orders
            *                   action                  : the target route action. not required.
            *                   id                      : the target route id. the value of this field name in this record will be passed as the id. not required.
            *                   fk                      : the foreign key name that the target controller is going to expect. the list page will append the primary key of this table. not required.
            *                   fk_title_field          : the name that will be passed to the target action as extra title text. not required.
            */                           
                if ($this->related_pages && sizeof($this->related_pages) > 0)
                {
                    foreach ($this->related_pages as $related_page ) { echo $this->related_page_anchor($related_page, $row); } 
                }


            foreach (array_keys($this->list_fields) as $field)
            {
                ?><td><?
                if (substr($field, -2) == '()')
                {
                    $method = substr($field, 0, strlen($field)-2);
                    echo $this->model_object->$method($row);
                }
                // ok... TODO fix this.. now that this uses mdb2. where is my schema introspection on appstart ?
                elseif ((stristr($this->list_fields[$field], ' date') != false) or strtolower($this->list_fields[$field]) == 'date')
                {
                    echo strftime(DATE_FORMAT, strtotime((string)$row->$field));
                }
                elseif (stristr($this->list_fields[$field], 'time') != false)
                {
                    // write out a nicely formatted time
                    echo strftime(TIME_FORMAT, strtotime((string)$row->$field));
                }
                elseif (strlen($row->$field)> 32)
                {
                        echo substr($row->$field, 0, 30).'...'; #todo make this hack break on words etc and not use a magic no
                }
                else
                {
                    echo stripslashes($row->$field);
                }?></td><?
            } ?>
              </tr>
        <?php
        }
    }

    public function list_paging($num_rows)
    {

        if ($num_rows <= $this->row_limit) {return false;}
        ?><table align="center" width="50%">
        <tr><td align="left"><?
        if ($this->paging_back >= 0)
        {
            ?><a href="<?=page_parameters('/^start$/');?>&amp;start=<?=$this->paging_back;?>">PREV</a><?
        }
        ?></td><td align=center><?
        $i=0; $l=1;

        
        for($i=0;$i < $num_rows;$i=$i+$this->row_limit) 
        {
            if ($i <> $this->start_limit)
            {
                ?><a href="<?=page_parameters('/^start$/');?>&amp;start=<?=$i.$pageextra;?>"><?=$l?></a><?
            }
            else
            {
                ?><span color="red"><?=$l;?></span><?
            }
            
            echo '&nbsp;';
            $l++;
        }
        ?></td><td align="right"><?
        if($this->paging_next < $num_rows)
        {
            ?><a href="<?=page_parameters('/^start$/');?>&amp;start=<?=$this->paging_next.$pageextra;?>">NEXT</a><?
        }
        ?></td></tr></table><?
    }

    public function draw_filters($filters)
    {
        /* this whole filter button thing is an enormous hack. todo is fix draw_filters() */

        ?><span class="button" id="bt_show_filters" <? if (isset($this->filter_object->has_filter_values) && $this->filter_object->has_filter_values) { echo 'action="h"'; }
?> onclick="
$('#filters').slideToggle('slow');
if ($(this).html() != 'Show filters') { $(this).html('Show filters'); } else { $(this).html('Hide filters'); }
        "><? if ( !isset($this->filter_object->has_filter_values) || !$this->filter_object->has_filter_values) {
            ?>Show filters</span><div id="filters" style="display: none"><? } else { ?> Hide filters</span><div id="filters" style="display: block"><? } ?>
    <form id="frm_filter" method="get"><? echo page_parameters('/^filter/,/^start$/', false, 'hidden');
            ?><table><tr><?
            $cnt=0;
            foreach ($this->filter_object->filters as $filter)
            {
                if ($cnt % 4 == 0 && $cnt > 0 && ($cnt < sizeof($this->filter_object->filters))) {
                    echo '</tr></table><table><tr>';
                }
                echo '<td>'; $this->filter_object->{'filter_'.$filter['type']}($filter);echo '</td>';
                $cnt++;
            }
            #leftover cells
            for ($i==$cnt;$i<5;$i++)
            { echo '<td>&nbsp;</td>'; }
        ?><tr/></table>
<input type="submit" value="Apply" />
        <input type="button" onclick="window.location ='<?=page_parameters('/^filter_/,/^start$/')?>'" value="Clear filters" />
        </form>
    </div><?
    }

    public function cm_view()
    {
        $edit_id = $_GET['view_id'];
        if (!$view_page) {$view_page = $this->view_page;}
        ?><h2>Viewing a<?
        switch(strtolower(substr($this->list_type, 0, 1))) {case 'a': case 'e': case 'i': case 'o': case 'u': echo 'n';}
        ?> <?=humanize($this->list_type)?></h2><?
        $sql = 'SELECT * FROM '.$this->schema_table.' WHERE '.$this->primary_key_field.' = '.$edit_id;
        $AR = new AR;
        $values = $AR->db->query($sql);AR::error_check($values);
        $values = $values->fetchRow();
        $this->model_object->update_attributes($values);
        $record = $this->model_object;
        require(App::$env->content_path.'/'.$view_page);
        $this->render_inline();
    }

    public function cm_edit()
    {
        $edit_id = $_GET['edit_id'];
        # pull out id's and suchlike
        $this->edit_page_title = str_replace('__id__', $edit_id, $this->edit_page_title); #todo fix this hack, replace with actual field names in some way
        ?><h2><?=$this->edit_page_title;?></h2><?
        ?><form method="post" enctype="multipart/form-data" action="<?=href_to(array('action' => 'update')).page_parameters('/^edit/')?>&edit_id=<?=$edit_id?>"><?

        $sql = 'SELECT * FROM '.$this->schema_table.' WHERE '.$this->primary_key_field." = '".$edit_id."'";
        $AR = new AR;
        $values = $AR->db->query($sql);AR::error_check($values);
        $values = $values->fetchRow();
        $this->model_object->update_attributes($values);
        $record = $this->model_object;

        $form_fields = $this->form_fields;
        if (isset($form_fields)) {
            forms::form(array_merge(array($this->primary_model, &$record), $form_fields));
        }
        if ($this->draw_form_buttons) {forms::form_buttons();}
        ?></form><?
        $this->render_inline();
    }

    public function cm_add()
    {
        ?><h2>Add a new <?=humanize($this->list_type);?></h2><?
        ?><form method="post" enctype="multipart/form-data" action="<?
        $parameters_to_remove = $parameters = '';
        if (isset($this->add_postback_parameters)) {$parameters_to_remove .= ','.$this->add_postback_parameters['filters']; $parameters.= '&'.$this->add_postback_parameters['parameters'];}

        echo url_to(array('action' => 'save')).page_parameters($parameters_to_remove).$parameters;?>"><?
            
        if (isset($_POST) && sizeof($_POST) > 0 )
        {
            $this->model_object->update_attributes($_POST[$this->primary_model]);
        }

        $record = $this->model_object;
        #automatically populate the model_object with the foreign keys
            foreach($this->foreign_keys as $key => $value)
            {
                $record->$key = $value;
            }

        $form_fields = $this->form_fields;
        if (isset($form_fields)) {
            forms::form(array_merge(array($this->primary_model, &$record), $form_fields));
        }
        if ($this->draw_form_buttons) {forms::form_buttons('save',false);}
        ?></form><?
        $this->render_inline();
    }

#------------------------------#
# default filter actions
#------------------------------#

    public function check_for_print()
    {
        if (isset($_GET['print']) && $_GET['print'] == 'y') {
            $this->layout = 'print';
            define('PRINTING_MODE', true); #hackety hack hack ? 
        }
        
    }

    public function is_logged_in()
    {
        if ( !isset($_SESSION[APP_NAME]['user_id']) || $_SESSION[APP_NAME]['user_id'] == '' )
        {
            if (isset($_POST['email']))
            {
                $email = $_POST['email'];
                $password = $_POST['password'];
                $user = new user;
                $user_id = $user->is_valid_user( $email, $password );
                if ( $user_id )
                {
                    $_SESSION[APP_NAME]['display_name'] = $user->first_name.' '.$user->last_name;
                    $_SESSION[APP_NAME]['username'] = $email;
                    $_SESSION[APP_NAME]['user_id'] = $user_id;

                    if (method_exists($this, 'assign_login_rights')) { $this->assign_login_rights(); }

                    #successful login! redirect to default. 
                        header('location: '.url_to(array('face' => 'cm')));
                }
                else
                {
                    $flash = "This email address and password combination was not found";$_GET['flash'] = $flash;
                    App::$route['controller'] = 'default_controller';
                    App::$route['action'] = 'login';
                    global $view_parameters; $view_parameters['hide_menu'] = true;
                }
            }
            else
            {
                $_GET['flash'] = null;
                App::$route['controller'] = 'default_controller';
                App::$route['action'] = 'login';
                global $view_parameters; $view_parameters['hide_menu'] = true;
            }
        }
        
    }
}
?>
