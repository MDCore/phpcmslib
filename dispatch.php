<?
#get the path, and remove the path from $_GET
if (sizeof($_GET) > 0) 
{
    $path = array_keys($_GET);$path = $path[0];
    unset($_GET[$path]);
    if ($path == 'index_html') {$path = 'default';}
}

$path_to_root = '../../..';

#initialize the application
    require ('./init.php');

#build the route
    $route = build_route($path);

    App::$route = $route;
    #echo '<pre>';print_r($route);echo '</pre>';
#load the controller
    $view_parameters = null;
    #load the face_controller for this face first
    if (App::require_controller('face_controller'))
    {
        #the face controller was found
        $face_controller = new face_controller;

        #before_controller_filter
            $face_controller->handle_controller_filter($face_controller->before_controller_filter, App::$route['controller']);
    }
    else
    {
        #do we require a face controller ? If so, create an error trigger here...
    }
    if (App::require_controller(App::$route['controller']))
    {
        #controller found!
        $controller = new App::$route['controller'];
    }
    if (!$controller) { trigger_error("Controller <i>".App::$route['face'].'/'.App::$route['controller']."</i> not found", E_USER_ERROR); }

    App::$controller = $controller;

    if (!App::$route['action']) { App::$route['action'] = App::$controller->default_action; }
    if (!App::$route['action']) { trigger_error("Controller <i>".App::$route['face'].'/'.App::$route['controller']."</i> has no default action and no action has been specified", E_USER_ERROR); }

    #before_controller_execute_filter
        $face_controller->handle_controller_filter($face_controller->before_controller_execute_filter, App::$route['controller']);

    #execute the action
        ob_start(); #cache the output
        App::$controller->execute_action();
        App::$render_contents = ob_get_contents(); #save the output, for later rendering
        ob_clean(); #drop the output contents

#load the layout
    #echo '<pre>';print_r(App::$route);print_r(App::$controller);echo '</pre>';die();
    if (App::$controller->layout) { render_layout(); } else { render_content(); } # the layout calls render_content which renders the view

#after_controller_filter
    $face_controller->handle_controller_filter($face_controller->after_controller_filter, App::$route['controller']);
?>
