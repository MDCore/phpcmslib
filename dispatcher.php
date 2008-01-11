<?
class dispatcher
{
    public function path_from_collection(&$collection)
    {
        #print_r($collection);#die();

        #get the path, and remove the path from $collection
        if (sizeof($collection) > 0)
        {
            $path = array_keys($collection);
            if ($path[0] != 'qs') { $path = $path[0];unset($collection[$path]); } else { $path = null; }
            unset($collection['qs']);
            
            if ($path == 'index_html') {$path = 'default';}
            return $path;
        }

    }

    public function process(&$collection = null)
    {
        if (!$collection) { $collection = &$_GET; }
        $path = $this->path_from_collection($collection);
        
        #build the route
            App::$route = route_from_path($path);

        #-------------------------------------------------------------------------------------------------
            
           #echo '<pre>';print_r(App::$route);echo '</pre>';
        #load the controller
            #load the face_controller for this face first
            if ($face_controller_path = App::require_this('controller', 'face_controller', App::$route['face'])) {
                #the face controller was found
                require($face_controller_path);
                $face_controller = new face_controller;
            }
            else {
                #a face controller is required
                    trigger_error("face_controller not found for <strong>".App::$route['face']."</strong> face", E_USER_ERROR); 
            }
        #-------------------------------------------------------------------------------------------------
            #before_controller_load_filter
                $face_controller->handle_controller_filter('before_controller_load', $face_controller);
            
        #-------------------------------------------------------------------------------------------------

            if ($controller_path = App::require_this('controller', App::$route['controller'], App::$route['face'])) {
                #controller found!
                require($controller_path);
                $controller = new App::$route['controller'];
            }
        #-------------------------------------------------------------------------------------------------
            
            if (!$controller) {
                // check for some 'oddities'
                if (App::$route['controller'] == 'favicon_ico_controller') {
                    http_header(404, true);
                }
               
                trigger_error("Controller <i>".App::$route['face'].'/'.App::$route['controller']."</i> not found", E_USER_ERROR);
            }

        #-------------------------------------------------------------------------------------------------

            #set the current controller and current face fields in App::
                App::$controller = $controller;
                App::$face = App::$route['face'];

            if (!App::$route['action']) { App::$route['action'] = App::$controller->default_action; }
            if (!App::$route['action']) { trigger_error("Controller <i>".App::$route['face'].'/'.App::$route['controller']."</i> has no default action and no action has been specified", E_USER_ERROR); }

        #-------------------------------------------------------------------------------------------------

            #before_controller_execute_filter /* execute this filter, on the app controller */
                $face_controller->handle_controller_filter('before_controller_execute', App::$controller);

        #-------------------------------------------------------------------------------------------------
            #execute the action, caching the output
                ob_start(); #cache the output
                App::$controller->execute_action();
                App::$controller->render_contents = ob_get_contents(); #save the output, for later rendering
                ob_clean(); #drop the output contents

        #-------------------------------------------------------------------------------------------------
        #do the rendering
            #echo '<pre>';print_r(App::$route);print_r(App::$controller);echo '</pre>';die();
                App::$controller->render();
        #-------------------------------------------------------------------------------------------------

        #after_controller_filter /* execute this filter, on the app controller */
            $face_controller->handle_controller_filter('after_controller', App::$controller);
    }
}
?>
