<?
/**
 * This file implements a CMS for Pedantic_Lib
 *
 * @category  PHP
 * @package   Pedantic_Lib
 * @author    Gavin van Lelyveld <gavin@pedantic.co.za>
 * @copyright 2007 Gavin van Lelyveld
 * @license   proprietary http://pedantic.co.za
 * @link      http://pedantic.co.za
 **/

/* TODO
 * - document properties of cm_controller (find out how)
 * - related pages doesn't have enough convention over configuration
 * - list body needs to use the schema introspection already being done
 */
class cm_controller extends action_controller {
    public $is_controller = true;
    public $layout = 'default';
    public $face = 'cm';
    public $default_action = 'cm_list';

    //default configuration
    public $allow_edit = true, $allow_add = true, $allow_delete = true, $allow_view = false;
    public $allow_filters = true, $allow_sort = true;
    public $row_limit = 30;
    public $show_record_selector = false;
    public $field_length_range = array(30, 55);

    public $list_sort_field = null, $list_sort_type = null;
    public $export = false;

    /*
     * now in /cm/cm_face_controller

    public $before_controller_load_filter = 'is_logged_in';
    public $before_controller_execute_filter = 'check_for_print';
     */
    function __construct()
    {
        parent::__construct();

        #the face_controller should be virtual;
        if (($this->route['controller'] == $this->route['face'].'_face') || ($this->route['controller'] == 'face')) {
            $this->virtual = true;
        }
        if ($this->virtual) {
            return true;
        }

        # todo document this all
        #foreign key(s)
        if (isset($_GET['fk'])) {
            $fk = $_GET['fk'];
            $fk = explode(',', $fk);
            foreach ($fk as $keyval) {
                $key = substr($keyval, 0, strpos($keyval, '~'));
                $value = substr($keyval, strpos($keyval, '~')+1);
                $this->foreign_keys[$key] = $value;
            }
        }
        else {
            $this->foreign_keys = array();
        }

        /* export */
        if (isset($_GET['export'])) {
            $this->export = $_GET['export'];
            if ($this->export == 'y' | $this->export == 'true') { $this->export = true; }
            if (isset($this->export_sql_query)) {
                $this->sql_query = $this->export_sql_query;
                $this->list_fields = $this->export_list_fields;
            }
            switch ($this->export) {
                case 'psv':
                    $this->export_delimiter = '|';
                    break;
                default:
                    $this->export_delimiter = ',';
                    break;
            }
        }

        if (!isset($this->list_type))       { $this->list_type =  singularize($this->route['controller']); }
        if (!isset($this->primary_model))   { $this->primary_model = $this->list_type; }
        if (!isset($this->list_title))      { $this->list_title = proper_nounize(pluralize($this->list_type)); } $this->list_title = proper_nounize($this->list_title);
        //deprecated? if (!isset($this->email_subject))   { $this->email_subject = $this->list_title; }

        //row limit, records per page
        if (isset($_GET['records_per_page'])) { $this->row_limit = $_GET['records_per_page']; }

        //add page title
        if (!isset($this->add_page_title)) {
          $this->add_page_title = 'Add a new '.humanize($this->list_type);
        }

        //edit page title
        if (!isset($this->edit_page_title)) {
          $this->edit_page_title = "Editing a";
          switch(strtolower(substr($this->list_type, 0, 1))) {case 'a': case 'e': case 'i': case 'o': case 'u': $this->edit_page_title .= 'n';}
          $this->edit_page_title .= ' '.humanize($this->list_type);
        }

        $this->draw_form_buttons = true;

        /* are we going into delete mode */
        if (isset($_GET['delete']) && $_GET['delete'] == 'y') {
            $this->show_delete = true;
        } else {
            $this->show_delete = false;
        }

        /* the title for the edit action_link */
        if (!isset($this->edit_link_title)) {
            $this->edit_link_title = 'Edit';
        }

        /* the title for the view action_link */
        if (!isset($this->view_link_title)) {
            $this->view_link_title = 'View';
        }

        //setup some objects
        $this->model_object = new $this->primary_model; #instantiate an object of this model so we can interrogate it
        $this->filter_object = new filter;

        //pull certain variables from the model
        $this->primary_key_field = $this->model_object->primary_key_field;
        $this->schema_table = $this->model_object->schema_table;

        //setup the SQL query for the list page
        $sql_pk = $this->schema_table.".".$this->primary_key_field." as __pk_field";
        if (!isset($this->sql_query)) {
            $this->sql_query = array(
                'SELECT' => $this->schema_table.'.*, '.$sql_pk,
                'FROM'   => $this->schema_table
            );
        } else {
          if (isset($this->sql_query['SELECT']) && $this->sql_query['SELECT'] == 'default') {
            $this->sql_query['SELECT'] = $this->schema_table.'.*, '.$sql_pk;
          }
          if (isset($this->sql_query['FROM']) && $this->sql_query['FROM'] == 'default') {
            $this->sql_query['FROM'] = $this->schema_table;
          }
        }
        if ($this->allow_filters ) {
            if ($this->filters) {
                //setup the filter names
                $this->filter_object->init($this->primary_model, $this->filters);
                $this->has_filters = true;
            } else {
                $this->has_filters = false;
            }
        }

        //some fk stuff
        if (!isset($this->foreign_key_title_prefix)) {$this->foreign_key_title_prefix = ' in ';}
    }

    /**
     * handles calling the list, edit, add, view, save, update and delete pages by
     * by friendly names.
     *
     * @param string $method_name The name of the method that was called
     * @param array  $params      The collection of method parameters that were part of the method call
     *
     * @return void
     */
    function __call($method_name, $params)
    {
        global $path_to_root;

        $this->action = $method_name;
        /*
         * automatic cm pages router
         * TODO: clean this up. calling with $this->$cm_page might be better ?
         */
            switch ($method_name) {
            case 'list':
                /*
                 *  list is a reserved word, so overriding cm_list involves a method named _list #todo recipe this
                 */
                if (method_exists($this, '_list')) {
                    $this->_list();
                }
                else {
                    $this->cm_list();
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

#------------------------------#
# Action Presets
#------------------------------#

    /**
     * Updates an existing record and related records, if applicable.
     * If the controller has a before_update or after_update method those are called as applicable.
     *
     * @param boolean $redirect_on_success Default true. If this is true it redirects back to the list, else allows continued processing
     *
     * @return void
     */
    public function cm_update($redirect_on_success = true) {
        $edit_id = $_GET['edit_id'];
        $result = $this->cm_update_core($edit_id, $_POST);

        switch ($result['result']) {
        case 'validation_failed':
            redirect_with_parameters(url_to(array('action' => 'edit')), "edit_id=".$edit_id."&flash=".$result['errors']);
            break;
        case 'success':
            if ($redirect_on_success) {
                redirect_with_parameters(url_to(array('action' => 'list')), "flash=".proper_nounize($this->list_type). " updated");
            }
            break;
        }
    }
    public function cm_update_core($edit_id, $full_collection)
    {
        if (method_exists($this, 'before_update')) { $this->before_update($edit_id); } #todo clean this up.... should be in model, maybe

        #print_r($_GET);print_r($full_collection);print_r($_FILES);
        $primary_model_object = new $this->primary_model;
        if (isset($full_collection[$this->primary_model])) {
            $primary_model_object
                ->find($edit_id)
                ->update_attributes($full_collection[$this->primary_model], true, true);
            if ($primary_model_object->is_valid()) {
                $record_updated = $primary_model_object->update();
            /*print_r($primary_model_object); print_r($record_updated);die();*/
            /* sanity checking; it better be updating and not saving! */
                if ($record_updated != $edit_id) {
                    trigger_error("Update of {$this->primary_model} failed. $record_updated != $edit_id.", E_USER_ERROR); die();
                }
            } else { $record_updated = false; }
        }
        else {
            $no_primary_to_save = true;
        }

        if ($record_updated | $no_primary_to_save) {
            foreach ($full_collection as $meta_model => $collection) {
                if ($meta_model != $this->primary_model) { /* make sure we are working with meta models */
                    $fk_field = foreign_keyize($this->primary_model);
                    $collection[$fk_field] = $edit_id; /* add the foreign key straight into the collection */

                    /* forms must singularize their model names, that is why this needs to be pluralized */
                    /* todo if the through model is singularized it just does _nothing_ */
                    if ($primary_model_object->through_model(pluralize($meta_model))) {
                        /* this is a join table */
                        $meta_model_object = new $meta_model;
                        $meta_model_object->delete("$fk_field = $edit_id"); #delete the records, to re-add them
                        $result = $meta_model_object->save_multiple($collection);
                    } else {
                        /* this is another table that has a foreign key of the primary table in it */
                        $meta_model_object = new $meta_model;
                        $meta_model_object
                            ->find(" WHERE $fk_field = $edit_id")
                            ->update_attributes($collection, true, true);
                        if (!$meta_model_object->is_valid()) {
                            redirect_with_parameters(url_to(array('action' => 'edit')), "edit_id=".$edit_id."&flash=".$meta_model_object->validation_errors);die();
                        }
                        if ($meta_model_object->count > 0) {
                            $meta_model_object->update();
                        } else {
                            /* This accounts for adding new records as meta models and not just updating existing ones.
                             * A good case for this is adding a comment to a collection of comments as part of another
                             * model.
                             */
                            $meta_model_object->save();
                        }
                    }

                    /* todo duplicate the meta-model code from cm_save */
                    /*
                     * this code used to be part of another hack which would ADD a record to a meta_model instead of updating.
                     * I removed the hack so this had to go. but I've left it here for when I want to do that.
                     *
                    $meta_model = new $meta_model($collection);
                    $meta_model->save();
                     */
                }
            }

            $this->handle_new_files($edit_id);

            if (method_exists($this, 'after_update')) { $this->after_update($edit_id); } #todo clean this up.... should be in model, maybe

            return array('result' => 'success');
        } else {
            return array('result' => 'validation_failed', 'errors' => $primary_model_object->validation_errors);
        }

    }

    /**
     * Saves an existing record and related records, if applicable.
     * If the controller has a before_save or after_save method those are called as applicable.
     *
     * @param boolean $redirect_on_success Default true. If this is true it redirects back to the list, else allows continued processing
     *
     * @return void
     */
    public function cm_save($redirect_on_success = true)
    {
        #debug('handling_save');print_r($_GET);print_r($_POST);print_r($_FILES);

        if (method_exists($this, 'before_save')) { $this->before_save(); } //todo clean this up.... should be in model, maybe

        $collection = $_POST[$this->primary_model];
        if (!$collection) {
            $collection = $_POST;
            $has_meta_data = false;
        } else {
            $has_meta_data = true;
        }
        // save the form data for the primary model
        $primary_model_object = new $this->primary_model;
        $primary_model_object->update_attributes($collection, true, true); /* with value changes, clean */

        if (!$primary_model_object->is_valid()) {
            $_GET['flash'] = $primary_model_object->validation_errors;
            $this->cm_add();
            return false;
        }

        $primary_record_id = $primary_model_object->save();

        if ($primary_record_id) { /* might not have one if saving failed e.g. validation */
            if ($has_meta_data) {
                #deal with related tables
                foreach ($_POST as $meta_model => $collection) {
                    if ($meta_model != $this->primary_model) {
                        $collection[foreign_keyize($this->primary_model)] = $primary_record_id; //add the foreign key straight into the collection

                        if ($primary_model_object->through_model(pluralize($meta_model))) {
                            $meta_model_object = new $meta_model;
                            #$meta_model_object->delete("$fk_field = $edit_id"); //delete the records, to re-add them
                            $meta_model_object->save_multiple($collection);
                        } else {
                            $meta_model_object = new $meta_model($collection);
                            if (!$meta_model_object->is_valid()) {
                                #delete the primary_record
                                $primary_model_object->delete($primary_record_id);
                                if (!$meta_model_object->is_valid()) { redirect_with_parameters(url_to(array('action' => 'list')), "flash=".$meta_model_object->validation_errors); die(); }
                            }
                            $meta_model_object->save(); #don't need the primary id, afaik
                        }
                    }
                }
            }
            $this->handle_new_files($primary_record_id);
            # callback here
            if (method_exists($this, 'after_save')) { $this->after_save($primary_record_id); } # todo should be in the model, or part of a broader callbacks framework

            if ($redirect_on_success) {
                redirect_with_parameters(url_to(array('action' => 'list')), "flash=New ".proper_nounize($this->list_type). " added");
            }
        }
        else {
            die("nothing to save");
        }
    }

    /**
     * Deletes a collection of records. The records are defined by a collection of ID's in $_POST['delete'].
     * If the controller has a before_save or after_save method those are called as applicable.
     * This has no redirect on success because this simple delete can be handled by AR
     *
     * @return void
     */
    public function cm_delete() {
        // delete these puppies
            $sql_delete = $this->primary_key_field." IN (";
            $records_deleted = 0;
            foreach ($_POST['delete'] as $delete_id)
            {
                if (is_int((int)$delete_id)) {
                    $sql_delete .= $delete_id .',';
                    $records_deleted += 1;
                }
            }
            if (substr($sql_delete, -1, 1) == ',') { $sql_delete = substr($sql_delete, 0, -1); }
            $sql_delete .= ");";

        // delete records
            $ign = $this->model_object->delete($sql_delete);

        // set message
            if ($records_deleted != 1) {$flash = proper_nounize(pluralize($this->list_type));} else {$flash = proper_nounize($this->list_type);}
            $flash = "Deleted $records_deleted ".$flash;

        redirect_with_parameters(url_to(array('action' => 'list')), "flash=$flash");
    }

#------------------------------#
# CM views
#------------------------------#

    /**
     * A list of records
     *
     * @return void
     */
    public function cm_list() {
        if (!isset($this->list_type)) {
            /* if the list_type property has not been set then something
             * has gone quite wrong. Probably your cm_controller __construct()
             * has not called parent __construct()
             */
            trigger_error("list_type not set but trying to draw cm_list(). Check that your controller's __construct() calls parent::__construct()", E_USER_ERROR);die();
        }

        /* settings tweaks if in print mode */
        if (defined('PRINTING_MODE') | $this->export !== false) {
            $this->allow_filters = false;
            $this->allow_add = false;
            $this->allow_edit = false;
            $this->allow_view = false;
            $this->allow_delete = false;
            $this->back_link = false;
            unset($this->category_actions);
            unset($this->related_pages);
            /* only for view/add/edit really $this->draw_form_buttons = false; */
            $this->row_limit = 1000000;
            $this->allow_sort = false;
            $this->field_length_range = array(1000, 10000); /* print all the text in long fields */
        }

        /* setup the list fields */
        $this->list_fields = split_aliased_string($this->list_fields);

        /* draw the list title */
        /* the foreign key description portion of the title */
        if (isset($_GET['fk_t'])) {
            $fk_t= $_GET['fk_t'];
            $this->foreign_key_title = $fk_t;
            if (isset($_GET['rfk_t'])) {
              $related_fk_title = explode('||', $_GET['rfk_t']);
              $this->foreign_key_title .= implode(' ', array_reverse($related_fk_title));
            }
        }
        else {
            $this->foreign_key_title = '';
        }

        if ($this->export === false) {
            ?><h2><?=$this->list_title;?><?=stripslashes($this->foreign_key_title);?></h2><?
        }

        /* draw the filters */
            if ($this->allow_filters && $this->has_filters) {
                echo $this->draw_filters($this->filter_object->filters);
            }

        if ($this->show_delete) {
            ?><form id="list_delete" method="post" action="<?=url_to(array('action' =>'delete')).page_parameters('', false)?>"><?
        }

    #--------- query, sql_query, sql query, sqlquery, xxxsql ---------------------------------------#
        if (!is_array($this->sql_query)) {
            $list_sql = SQL_explode($this->sql_query);
        } else {
            $list_sql = $this->sql_query;
        }

        #if (!isset($list_sql['WHERE']) || $list_sql['WHERE'] == '') { $list_sql['WHERE'] = '1=1'; }
        if (property_exists($this, 'foreign_keys')) {
            if (isset($list_sql['WHERE']) && !is_array($list_sql['WHERE'])) {
                #oops.. we have a specified WHERE but it's not an array. better make it one quick'
                $list_sql['WHERE'] = array($list_sql['WHERE']);
            }
            foreach ($this->foreign_keys as $key => $value) {
                $list_sql['WHERE'][] = " AND $key='$value'";
            }
        }

        if (property_exists($this, 'filter_object')) {
            $filter_sql = $this->filter_object->sql_criteria();
        }

        if (!($filter_sql == false)) {
            #join from's
            if ($filter_sql['FROM']) { foreach ($filter_sql['FROM'] as $filter_from) { $list_sql['FROM'] .= ' ' . $filter_from; }}

            #join where's
            foreach ($filter_sql['WHERE'] as $filter_where) { $list_sql['WHERE'][] = ' AND '. $filter_where; }
        }

        #sorting
        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
            #asc or desc
            if (substr($sort, -3) == 'asc') {
                $sort_type = 'ASC';
            }
            else {
                $sort_type = 'DESC';
            }
            $sort = substr($sort, 0, strlen($sort) - strlen($sort_type)-1);
            $this->list_sort_field = $sort;
            $this->list_sort_type = $sort_type;
        }
        if (!$this->list_sort_field && in_array('sort_order', $this->list_fields)) {
            #default #todo put this into some defaults script
            #todo add to magic_stuff documentation
            $this->list_sort_field = "sort_order ASC";
        }
        if ($this->list_sort_field) {
            $list_sql['ORDER BY'] = $this->list_sort_field.' '.$this->list_sort_type;
        }

        #turn the array into a string
            $this->list_sql = $list_sql;
            #print_r ( $list_sql );
            $results_query = SQL_implode($list_sql);
            $sql_pk = $this->schema_table.".".$this->primary_key_field." as __pk_field";
            $results_query = str_replace( '__pk__', $sql_pk, $results_query );

        if ($this->debug_sql) { print_r($results_query); }

        $AR = new AR;
        #get the number of records
            $sql_no_of_records = $results_query;
            $db_no_of_records = $AR->db->query($sql_no_of_records);
            AR::error_check($db_no_of_records);
            $no_of_records = $db_no_of_records->numRows(); #count

        #initialize the paging object
            $this->paging = new cm_paging($no_of_records);
            $this->paging->records_per_page = $this->row_limit;

        #open the actual recordset
            $results_list = $AR->db->query($results_query.' LIMIT '.$this->paging->limit_sql());
            AR::error_check($results_list);

        if ($this->export === false) {

            ?><div class="list_rows"><?=$this->paging->page_description($this->list_type);?> <?

            if (property_exists($this, 'filter_object')) {
                echo $this->filter_object->match_text($no_of_records);
            }
            ?></div><?
            ?><div class="list_wrapper"><?
                ?><table class="list"><?=$this->list_header();?><?=$this->list_body($results_list);?></table><?
                ?></div><? if (!defined('PRINTING_MODE')) { ?><div class="paging"><?=$this->paging->paging_anchors();?></div><? }

            if ($this->show_record_selector) {
                ?><div id="record_selector_buttons_container"><input disabled="disabled" type="button" id="bt_select_record" value="Select <?=$this->list_type;?>" onclick="window.parent.select_record_callback(currently_selected_row.val());" /><input type="button" id="bt_cancel_record_selector" value="cancel" onclick="window.parent.cancel_record_callback();" /></div><?
            }

            ?><div><?
            if ($this->show_delete) { ?><input type="submit" value="Delete selected" onclick="return confirm('Are you sure you want to delete these <?=humanize(pluralize($this->list_type))?> ?');">&nbsp;<? }
            if (isset($this->return_page )) {$return_page = $this->return_page ;} else { $return_page = pluralize($this->list_type); }  #XXX
            ?> </div><?

   if ($this->allow_add || $this->allow_delete || $this->back_link || isset($this->category_actions) || isset($this->show_csv_export)) {
                ?><div class="category_actions"><? #todo document category_actions
                if (isset($this->category_actions)) {
                    foreach ($this->category_actions as $value => $url) {
                        ?><a href="<?=$url;?>"><?=$value;?></a><br /><?
                    }
                }
                if ((isset($this->export_sql_query) && isset($this->export_list_fields)) | $this->show_csv_export === true) { ?><a href="<?=page_parameters('');?>&amp;export=csv">Export to CSV</a><br /><? }
                if ($this->back_link) {
		  if (is_array($this->back_link)) {
		    $back_link_title = array_keys($this->back_link); $back_link_title = $back_link_title[0];
		    $back_link_url = array_values($this->back_link); $back_link_url = $back_link_url[0];
                    $back_link_url = url_to($back_link_url).page_parameters('/^fk/,/^rfk/', false);
		  } else {
		    $back_link_title = humanize($this->back_link);
                    $back_link_url = url_to($this->back_link).page_parameters('/^fk/,/^rfk/', false);
                  }
                  ?><a href="<?php

                  if (isset($_GET['rfk'])) {
                    /* get the last value for the fk and then pop it */
                    $rfk = explode('||', $_GET['rfk']);
                    $back_link_url.='&amp;fk='.$rfk[sizeof($rfk)-1];
                    array_pop($rfk);
                    if ($rfk) {
                      $back_link_url.='&amp;rfk='.implode('||', $rfk);
                    }
                  }

                  if (isset($_GET['rfk_t'])) {
                    /* get the last value for the fk_t and then pop it */
                    $rfk_t = explode('||', $_GET['rfk_t']);
                    $back_link_url.='&amp;fk_t='.$rfk_t[sizeof($rfk_t)-1];
                    $current_fk_t = array_pop($rfk_t);
                    if ($rfk_t) {
                      $back_link_url.='&amp;rfk_t='.implode('||', $rfk_t);
                    }
                  }

                  echo $back_link_url;
                  ?>">Back to <?=$back_link_title;?><? if (isset($current_fk_t)) { echo ' '.$current_fk_t; } ?></a><br /><?
                }

                if ($this->allow_add) { ?><a accesskey="a" href="<?=url_to(array('action' =>'add')).page_parameters('', false);?>">Add a new <?=humanize($this->list_type);?></a><br /><? }
                if ($this->allow_delete) { ?><a href="<?=page_parameters('');?>&amp;delete=y">Delete <?=humanize(pluralize($this->list_type));?></a><br /><? }
                ?></div><?
            }
        } else {
            header("Content-Type: text/csv");
            #header("Content-Length: " . $filesize);
            $now = strftime(SQL_DATE_TIME_FORMAT, time());
            $now = str_replace(':', '', $now);
            $now = str_replace(' ', '-', $now);
            header("Content-Disposition: attachment; filename=\"csv_export-$now.csv\";" );
            $this->layout = false; /* we don't need a layout for CSV, natch */
            $this->list_header();
            $this->list_body($results_list);
        }
        $this->render_inline();
    }

    /**
     * The edit page for a record
     *
     * @return void
     */
    public function cm_edit() {
        $this->render_inline();
        if (!$this->allow_edit) {
            echo 'Edit not allowed';
            return true;
        }

        /* $_GET['edit_id'] is still here for backwards compat... with other parts of the code. I'm too lazy to change it all right now */
        if (isset($_GET['edit_id']) && $_GET['edit_id'] != '') {
            $edit_id = $_GET['edit_id'];
        } else {
            $edit_id = App::$route['id'];
        }
        $record = $this->model_object->find($edit_id);

        if ($record && $record->count > 0) {
            //valid record
        } else {
            echo 'No '.humanize($this->primary_model).' found.';die();
        }

        # pull out id's and suchlike
        $this->edit_page_title = str_replace('__id__', $edit_id, $this->edit_page_title); #todo fix this hack, replace with actual field names in some way
        ?><h2><?=$this->edit_page_title;?></h2><?
        ?><form method="post" enctype="multipart/form-data" action="<?=url_to(array('action' => 'update')).page_parameters('/^edit/')?>&edit_id=<?=$edit_id?>"><?

        $form_fields = $this->form_fields;
        if (isset($form_fields)) {
            forms::form(array_merge(array($this->primary_model, &$record), $form_fields));
        }
        if ($this->draw_form_buttons) {forms::form_buttons();}
        ?></form><?
        $this->render_inline();
    }

    public function cm_view() {
        $this->render_inline();
        if (!$this->allow_view) {
            echo 'View not allowed';
            return true;
        }

        /* $_GET['view_id'] is still here for backwards compat... with other parts of the code. I'm too lazy to change it all right now */
        if (isset($_GET['view_id']) && $_GET['view_id'] != '') {
            $view_id = $_GET['view_id'];
        } else {
            $view_id = App::$route['id'];
        }
        $record = $this->model_object->find($view_id);

        if ($record && $record->count > 0) {
            //valid record
        } else {
            echo 'No '.humanize($this->primary_model).' found.';die();
        }

        # pull out id's and suchlike
        $this->view_page_title = str_replace('__id__', $view_id, $this->view_page_title); #todo fix this hack, replace with actual field names in some way
        ?><h2><?=$this->view_page_title;?></h2><?
        ?><form method="post" enctype="multipart/form-data"><?

        $form_fields = $this->form_fields;
        /* force the fields to be readonly */
        for ($i = 0; $i < sizeof($form_fields); $i++) {
          if ($form_fields[$i][1] !== 'subheading') {
            $form_fields[$i]['readonly'] = 'readonly';
          }
        }

        if (isset($form_fields)) {
            forms::form(array_merge(array($this->primary_model, &$record), $form_fields));
        }
        ?></form><?
        $this->render_inline();
    }

    /**
     * The add form for a new record
     *
     * @return void
     */
    public function cm_add() {
        $this->render_inline();
        ?><h2><?=$this->add_page_title;?></h2><?

        if (!$this->allow_add) {
            echo 'Add is not allowed';
            return true;
        }

        ?><form id="add_form" method="post" enctype="multipart/form-data" action="<?
        $parameters_to_remove = $parameters = '';
        if (isset($this->add_postback_parameters)) {$parameters_to_remove .= ','.$this->add_postback_parameters['filters']; $parameters.= '&'.$this->add_postback_parameters['parameters'];}

        echo url_to(array('action' => 'save')).page_parameters($parameters_to_remove).$parameters;?>"><?

        if (isset($_POST) && sizeof($_POST) > 0 ) {
            $this->model_object->update_attributes($_POST[$this->primary_model], true, true);
        }

        $record = $this->model_object;
        #automatically populate the model_object with the foreign keys
            foreach($this->foreign_keys as $key => $value) {
                $record->$key = $value;
            }

        $form_fields = $this->form_fields;
        if (isset($form_fields)) {
            forms::form(array_merge(array($this->primary_model, &$record), $form_fields));
        }
        if ($this->draw_form_buttons) {
            forms::form_buttons('save',false);
        }
        ?></form><script type="text/javascript">$(function() { $($('input,select,textarea', '#add_form')[0]).focus(); });</script><?
    }

#------------------------------#
# various helper functions
#------------------------------#

    function related_page_anchor($related_page, $row) {

      /* check for a callback first */
      if (isset($related_page['callback'])) {
	$process = $this->{$related_page['callback']}($row);
      } else {
	$process = true;
      }

      if ($process === true) { /* draw this related page as normal */

	if (!isset($related_page['controller'])) {$related_page['controller'] = $related_page[0];}

	$target = array(
	  'controller' => $related_page['controller'],
	  'action'     => $related_page['action']
	  );
	if (isset($related_page['id'])) { $target['id'] = $row->{$related_page['id']}; }
	?><td class="action_link"><a href="<?echo url_to($target);

        if (isset($related_page['append_page_parameters'])) {
            echo page_parameters($related_page['append_page_parameters']);
        }
        else {
            echo '?p=y';
        }

        if (isset($related_page['fk'])) {
            if (!isset($related_page['fk_field'])) { $fk_field = $this->model_object->primary_key_field; } else { $fk_field = $related_page['fk_field']; }
            ?>&amp;fk=<?=$related_page['fk'];?>~<? echo $row->$fk_field;
        }
        if (isset($related_page['fk_title_field'])) { ?>&amp;fk_t=<? echo urlencode($this->foreign_key_title_prefix.$row->{$related_page['fk_title_field']}); }

        /* the additional related page titles and foreign keys, for going up and down a tree */
        if (isset($_GET['fk'])) {
          $related_fk = $_GET['rfk'];
          $related_fk_title = $_GET['rfk_t'];

          if ($related_fk != '') { $related_fk .= '||'; }
          $related_fk .= $_GET['fk'];
          if ($related_fk_title != '') { $related_fk_title .= '||'; }
          $related_fk_title .= urlencode($_GET['fk_t']);

          echo '&amp;rfk='.$related_fk.'&amp;rfk_t='.$related_fk_title;
        }

        ?>"><?

        if (isset($related_page['title'])) { echo $related_page['title']; } else echo h(proper_nounize($related_page['controller']));

        ?></a></td><?
      } elseif ($process === false) { /* draw a blank link */
        ?><td>&nbsp;</td><?
      } else { /* must be a callback result */
       echo $process;
      }

    }

    public function handle_new_files($primary_record_id)
    {
        #print_r($_GET);print_r($_POST); print_r($_FILES);
        #file uploads #todo get this working for meta_models
        foreach ($_FILES as $model => $model_files) {
            $upload = new upload;
            foreach ($model_files['name'] as $field_name => $file) {
                /* check force_new */
                $model_object = new $model;
                if (isset($model_object->cm_force_files_as_new) && $model_object->cm_force_files_as_new == true) {
                    $force_new = true;
                } else {
                    $force_new = false;
                }

                $upload->load($model, $field_name, $primary_record_id, $force_new);
                if ($upload->file_uploaded()) {
                    $upload->save();
                }
            }
        }
        #die();
    }

    public function list_header() {
        if ($this->export === false) {
            ?><thead><tr><?
            if ($this->show_record_selector) { ?><th class="record_selector_column">&nbsp;</th><? }
            if ($this->show_delete) { ?><th><input type="checkbox" id="delete_all" name="delete_all" onclick="select_all_rows();" value="on" /></th><? }
            if (!$this->show_delete && $this->allow_edit) {
                ?><th>&nbsp;</th><?
            }
            if (!$this->show_delete && $this->allow_view) {
                ?><th>&nbsp;</th><?
            }
            if ($this->related_pages && sizeof($this->related_pages) > 0) {
                foreach ($this->related_pages as $related_page) { ?><th>&nbsp;</th><? }
            }

            foreach ($this->list_fields as $header => $alias) {
                ?><th <?

                if ($this->list_sort_field == $header) {
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
                    if ($this->list_sort_field == $header) {
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
        } else {
            foreach ($this->list_fields as $header => $alias) {
                $header_row .= '"'.$header.'"'.$this->export_delimiter;
            }
            $header_row = substr($header_row, 0, -1)."\r\n";
            echo $header_row;
        }

    }

    public function list_body($results_list) {
        /* decide what to do with each field, before the loop, instead of in the loop */
        $list_field_descriptors = array();
        foreach (array_keys($this->list_fields) as $field) {
            if (substr($field, -2) == '()') {
                $method = substr($field, 0, strlen($field)-2);
                $list_field_descriptors[$field] = array('call_method', $method);
            }
            // ok... TODO fix this to use the schema introspection already being done
            // Problem is that these field names might be different to the schema field names.
            elseif ((stristr($this->list_field_descriptors[$field], ' date') != false) or strtolower($this->list_field_descriptors[$field]) == 'date') {
                $list_field_descriptors[$field] = array('date');
            }
            elseif (stristr($this->list_field_descriptors[$field], 'time') != false) {
                $list_field_descriptors[$field] = array('time');
            }
            elseif (isset($this->field_length_range)) {
                $list_field_descriptors[$field] = array('split', $this->field_length_range);
            }
            else {
                $list_field_descriptors[$field] = array('');
            }
        }

        ob_start();
        $no_of_related_pages = sizeof($this->related_pages); //it's faster to do the sizeof here than inside the loop
        $list_fields_field_names = array_keys($this->list_fields);

        /* the start of the big loop */
        while ($row = $results_list->fetchRow()) {
            if ($this->export === false) {
                ?><tr class="odd"><?
                if ($this->show_record_selector) { ?><td class="record_selector_column"><input type="radio" class="record_selector_row" id="record_selector_<?=$row->__pk_field;?>" name="record_selector[]" value="<?=$row->__pk_field;?>"  onclick="cm_select_record(this, <?=$row->__pk_field;?>);" /></td><? }
                if ($this->show_delete) { ?><td><input type="checkbox" class="delete_row" name="delete[]" value="<?=$row->__pk_field;?>" /></td><? }
            }

            /* if we are not in delete mode and editing is allowed */
            if (!$this->show_delete && $this->allow_edit) {
                ?><td class="action_link"><a href="<?=url_to(array('action' => 'edit', 'id' => $row->__pk_field)).page_parameters('/^edit/');?>"><?=$this->edit_link_title;?></a></td><?
            }

            /* if we are not in delete mode and viewing is allowed */
            if (!$this->show_delete && $this->allow_view) {
                # get or set the view_link_title
                    ?><td class="action_link"><a href="<?=url_to(array('action' => 'view', 'id' => $row->__pk_field)).page_parameters('/^view/');?>&"><?=$this->view_link_title;?></a></td><?
            }

            /**
             * a related page draws an extra action link in the list next to, say, edit or delete like so:
             *     [ edit ] [ delete ] [ related ]
             *
             *     This is the structure of a related page:
             *           It is an array of arrays: each related page is a record in the primary array.
             *           A single record is structured like so:
             *                   title                   : the title of the related page. e.g. "related" would be the title in the example above. If this is not set then the target controller name will be used.
             *                   controller              : the target route controller, without _controller appended. E.g. Orders
             *                   action                  : the target route action. not required.
             *                   id                      : the target route id. the value of this field name in this record will be passed as the id. not required.
             *                   fk                      : the foreign key name that the target controller is going to expect. the list page will, by default, append the primary key of this table, unless fk_field is set. not required.
             *                   fk_field                : the field name to use for the value of the foreign_key field. not required.
             *                   fk_title_field          : the name that will be passed to the target action as extra title text. not required.
             *                   append_page_parameters  : setting this values causes page_parameters() to be called with the value of this property. not required.
             *                   callback                : A function which accepts a row and returns either true to draw the link as per normal, false for no link or a string with a replacement <td><a></a></td>
             */
            if ($this->related_pages && $no_of_related_pages > 0) {
                foreach ($this->related_pages as $related_page ) { echo $this->related_page_anchor($related_page, $row); }
            }

            /* here we actually write out the value of the field */
            foreach ($list_fields_field_names as $field) {
                $this_field_descriptor = $list_field_descriptors[$field];
                if ($this->export === false ) {
                    ?><td><?
                }

                switch ($this_field_descriptor[0]) {
                case 'call_method':
                    if ($this->export !== false) { echo '"'; }
                    echo $this->model_object->{$this_field_descriptor[1]}($row);
                    if ($this->export !== false) { echo '"'; }
                    break;
                case 'date':
                    echo strftime(DATE_FORMAT, strtotime((string)$row->$field));
                    break;
                case 'time':
                    echo strftime(TIME_FORMAT, strtotime((string)$row->$field));
                    break;
                case 'split':
                    $full_phrase = $row->$field;
                    /* this checks that splitting needs to occur before doing the split and tooltip. split_on_word already checks for
                     * string length but I'm doing it here so that I don't have to show the tooltip unnecessarily
                     */
                    if (isset($full_phrase{$this_field_descriptor[1][1]-1})) {
                        $shortened_phrase = split_on_word(stripslashes($row->$field), $this_field_descriptor[1], true, true);
                        if (!defined('PRINTING_MODE')) {
                            ?><a class="tooltip"><?=$shortened_phrase;?><span><?=h(stripslashes($row->$field));?></span></a><?
                        }
                    break;
                    }
                default:
                    if ($this->export !== false) { echo '"'; }
                    echo h(stripslashes($row->$field));
                    if ($this->export !== false) { echo '"'; }
                }
                if ($this->export === false ) {
                    ?></td><?
                } else {
                    echo $this->export_delimiter;
                }
            }

            if ($this->export === false ) {
                ?></tr><?
            } else {
                echo "~~~!~~~";
            }
        }
        $output = ob_get_clean();
        if ($this->export === false) {
            echo $output;
        } else {
            /* strip trailing comma's */
            $output = str_replace($this->export_delimiter.'~~~!~~~', "\r\n", $output);
            echo $output;
        }
    }

    public function draw_filters($filters) {
        /* this whole filter button thing is an enormous hack. todo is fix draw_filters() */
?><span class="button" id="bt_show_filters" <? if (!($this->always_hide_filters) && isset($this->filter_object->has_filter_values) && $this->filter_object->has_filter_values) { echo 'action="h"'; }
?> onclick="
$('#filters').slideToggle('fast');
if ($(this).html() != 'Show filters') { $(this).html('Show filters'); } else { $(this).html('Hide filters'); }
        "><? if ($this->always_hide_filters || !isset($this->filter_object->has_filter_values) || !$this->filter_object->has_filter_values) {
            ?>Show filters</span><div id="filters" style="display: none"><? } else { ?> Hide filters</span><div id="filters" style="display: block"><? } ?>
    <form id="frm_filter" method="get"><? echo page_parameters('/^filter/,/^page_no$/', false, 'hidden');
            ?><table><tr><?
            $cnt=0;
            $no_of_filter_object_filters = sizeof($this->filter_object->filters);
            foreach ($this->filter_object->filters as $filter) {
                if ($cnt % 4 == 0 && $cnt > 0 && ($cnt < $no_of_filter_object_filters)) {
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
        <input type="button" onclick="window.location ='<?=page_parameters('/^filter_/,/^page_no$/')?>'" value="Clear filters" />
        </form>
    </div><?
    }
}
?>
