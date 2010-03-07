<?
function render_shared_partial($partial_name) {
  global $path_to_root;
  require($path_to_root.'/'.App::$route['face'].'/layouts/_'.$partial_name.'.php');
}
/*
 * this is just a nicer name for the layout to use
 */
function render_content() {
  App::$controller->render_content();
}
?>
