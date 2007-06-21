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
        $controller = new App::$route['controller']; #pre-render actions may occur here. e.g. saving records etc. This may redirect from here and stop execution
    }
    if (!$controller) { trigger_error("Controller ".App::$route['face'].'/'.App::$route['controller']." not found", E_USER_ERROR); }

    App::$controller = $controller;

    #before_controller_execute_filter
        $face_controller->handle_controller_filter($face_controller->before_controller_execute_filter, App::$route['controller']);
    
#load the layout
    #print_r(App::$route);print_r(App::$controller);die();
    require $path_to_root.'/'.App::$route['face'].'/layouts/'.App::$controller->layout.'.php';
    /*
        (the layout automagically calls the view)
     */

#after_controller_filter
    $face_controller->handle_controller_filter($face_controller->after_controller_filter, App::$route['controller']);
?>
