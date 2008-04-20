<?php
/* TODO:
 * rebuild this class! it is teh olds
 */
class cm_model_upload extends AR {
    
    var $save_path, $preview_path;
    function __construct()
    {
        parent::__construct(); 
        $this->save_path = App::$env->root.'/uploads';
        $this->preview_path .= App::$env->url.'/uploads';
    }
    function load_from_files()
    {
        $model = $this->model;$field_name = $this->field_name;$record_id = $this->record_id;
        
        if ($_FILES[$model]['size'][$field_name] > 0 && is_uploaded_file($_FILES[$model]['tmp_name'][$field_name])) {
            /* set the properties, yo */
            $this->original_filename = $_FILES[$model]['name'][$field_name];
            $this->file_type = $_FILES[$model]['type'][$field_name]; 
            $this->file_size = $_FILES[$model]['size'][$field_name];
            $this->model = $model;
            $this->field_name = $field_name;
            $this->record_id = $record_id;
            $salt = explode('/', $_FILES[$model]['tmp_name'][$field_name]);
            $this->salt = substr(end($salt), 3);
        }
    }
    function file_exists()
    {
        if ($this->basename() != '') {return true;} else {return false;}
    }
    function file_uploaded()
    {
        if ($_FILES[$this->model]['size'][$this->field_name] > 0 
            && is_uploaded_file($_FILES[$this->model]['tmp_name'][$this->field_name])) {
            return true;
        } else {
            return false;
        }
        
    }
    function load($model, $field_name, $record_id, $force_new = false)
    {
        //$sql = "SELECT * FROM uploads WHERE model = '$model' AND field_name = '$field_name' AND record_id = '$record_id';";
        $this->find_by_model_and_field_name_and_record_id($model, $field_name, $record_id);
        //$result = $this->db->query($sql);
        //$row = $result->fetchRow(MDB2_FETCHMODE_OBJECT);
        if ($this->count > 0 && !$force_new) {
            $this->new_record = false;
        } else {
            $this->new_record = true;
        }
        $this->model = $model;
        $this->field_name = $field_name;
        $this->record_id = $record_id;
    }

    function before_save()
    {
        $this->load_from_files();
    }
    function save()
    {
        $this->before_save();
        #save to the db
        if ($this->new_record) {
            $sql = "INSERT INTO uploads 
                (original_filename, file_type, file_size, model, field_name, record_id, salt) VALUES (
           '".$this->original_filename."', 
           '".$this->file_type."', 
           '".$this->file_size."', 
           '".$this->model."', 
           '".$this->field_name."', 
           '".$this->record_id."', 
           '".$this->salt."'
            );" ;
        } else {
            #remove old file
            # TODO

            $sql = "UPDATE uploads SET
            original_filename = '".$this->original_filename."', 
            file_type = '".$this->file_type."', 
            file_size = '".$this->file_size."', 
            field_name = '".$this->field_name."', 
            record_id = '".$this->record_id."', 
            salt = '".$this->salt."'
            WHERE id = '".$this->id."';";
        }
        $this->db->query($sql);
        $this->after_save();
    } 
    function after_save()
    {
        #save the file
        //var_dump($_FILES);
        if ($this->file_uploaded()) {
            $saved = move_uploaded_file($_FILES[$this->model]['tmp_name'][$this->field_name], $this->save_filename());
            #if (!$saved) { die($this->field_name.' file could not be moved');}
        }
    }
    function file_extension()
    {
        $file_ext = explode('.', $this->original_filename); $file_ext = end($file_ext);
        return $file_ext;
    }
    function save_filename()
    {
        #print_r( $this);
        return $this->save_path.'/'.$this->basename();
    }
    function display_filename()
    {
        if ($this->basename()) {
            return $this->preview_path.'/'.$this->basename();
        }
    }
    function basename()
    {
        if ($this->salt) {
            return $this->record_id.$this->salt.'.'.$this->file_extension();
        }
    }
    function delete()
    {
        /* delete from uploads table */
        $sql = "DELETE FROM uploads WHERE id=".$this->id;
        $this->db->query($sql);
        
        /* delete from uploads folder */
        global $path_to_root;
        $filename = $path_to_root.'/uploads/'.$this->basename();
        if (file_exists($filename) && $this->basename() != '') {
            unlink($filename);
        }
    }
}
?>
