<?
function render_layout()
{
    global $view_parameters, $path_to_root;
    #echo '<pre>';print_r(App::$route);print_r(App::$controller);echo '</pre>';die();

    global $view_parameters; if($view_parameters) { foreach ($view_parameters as $variable => $value) { $$variable = $value; } }
    require $path_to_root.'/'.App::$route['face'].'/layouts/'.App::$controller->layout.'.php';

}

function render_content()
{
    #print_r(App::$route);die();

    global $view_parameters; if($view_parameters) { foreach ($view_parameters as $variable => $value) { $$variable = $value; } }
    if (App::$controller->action_rendered_inline)
    {
        echo App::$render_contents; #dump the action rendered content
    }
    else
    {
        App::$controller->render_view();
    }
}

function render_shared_partial($partial_name)
{
    global $path_to_root;
    require($path_to_root.'/'.App::$route['face'].'/layouts/_'.$partial_name.'.php');
}

function render($view)
{

}

?>
