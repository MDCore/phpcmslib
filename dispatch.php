<?
if (isset($config['profiling']) && $config['profiling'] == true) apd_set_pprof_trace();
$path_to_root = '../../..';
require ('./init.php');

class dispatch
{
    private function path_from_collection(&$collection)
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
            if ($face_controller_path = App::require_this('controller', 'face_controller'))
            {
                #the face controller was found
                require($face_controller_path);
                $face_controller = new face_controller;
            }
            else
            {
                #a face controller is required
                    trigger_error("face_controller not found for <strong>".App::$route['face']."</strong> face", E_USER_ERROR); 
            }
        #-------------------------------------------------------------------------------------------------
            #before_controller_load_filter
                $face_controller->handle_controller_filter('before_controller_load', $face_controller);
            
        #-------------------------------------------------------------------------------------------------

            if ($controller_path = App::require_this('controller', App::$route['controller']))
            {
                #controller found!
                require($controller_path);
                $controller = new App::$route['controller'];
            }
        #-------------------------------------------------------------------------------------------------
            
            if (!$controller) { trigger_error("Controller <i>".App::$route['face'].'/'.App::$route['controller']."</i> not found", E_USER_ERROR); }

        #-------------------------------------------------------------------------------------------------

            App::$controller = $controller;

            if (!App::$route['action']) { App::$route['action'] = App::$controller->default_action; }
            if (!App::$route['action']) { trigger_error("Controller <i>".App::$route['face'].'/'.App::$route['controller']."</i> has no default action and no action has been specified", E_USER_ERROR); }

        #-------------------------------------------------------------------------------------------------

            #before_controller_execute_filter
                $face_controller->handle_controller_filter('before_controller_execute');

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

        #after_controller_filter
            $face_controller->handle_controller_filter('after_controller');
    }

}
?>
