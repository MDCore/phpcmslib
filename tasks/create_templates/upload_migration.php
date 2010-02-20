<?php
$sys->table('uploads', array(
    array('original_filename', 'string'),
    array('file_type', 'string'),
    array('file_size', 'float'),
    array('model', 'string'),
    array('field_name', 'string'),
    array('record_id', 'integer'),
    array('salt', 'string'),
    'timestamps'
    )
);
?>
