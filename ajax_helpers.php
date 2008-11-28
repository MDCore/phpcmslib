<?
/* these clases are, essentially, wrappers around jquery methods. */
class ajax
{
    /* structure of client_side_parameters:
     * array of elements of this structure: 'name for url' => 'DOM ID'
     */
    public function get_with_callback($url, $client_side_parameters, $callback_function) {
    trigger_error("get_with_callback not implemented yet", E_USER_ERROR);die();
        /*
         * sort out the client_side_parameters
         */
        /*foreach ($client_side_parameters as $param_name) {
            ?>
        }
        ?>$.get('<?=$url;?>', <?

    , function(data) { <?=$callback_function;?>(data); } );<?
         */
    }
}
?>
