<?
class action_controller
{
    public $has_rendered = false;
    public $layout = null;
    public $face = "site";
    public $virtual = false;

    public $before_action_filter = null, $after_action_filter = null;
    public $before_controller_load_filter = null, $before_controller_execute_filter = null, $after_controller_filter = null;
    /*
     * these filters work like so:
     * > $filter = array('method_name_1, method_name_2', 'only' => 'action_1, action_2, action3', 'except' => 'action_4');
     * OR
     * > $filter = 'method_name';
     * OR
     * > $filter = 'method_name, method_name_2';
     *
     * only will only execute that method for the specified actions / controllers.
     * except will not execute that method for the specified actions / controllers, but will for all others.
     * only and except are mutually exclusive. Using both will cause an error.
     */

    public function handle_controller_filter($filter, $controller = null)
    {
        /*
         * face controllers execute controller-level filters on their child controllers by default.
         *  That is why the filters are defined in the face controller but executed on the current controller.
         *  The exception to this is the before controller load filter, which is executed on the face_controller, 
         *  since we may want to switch controllers
         */

        if ($controller == null) {$controller = App::$controller; }
        
        $only = $except = null;

        switch ($filter)
        {
            case 'before_controller_execute':
                $filter = $controller->before_controller_execute_filter;
                break;
            case 'after_controller':
                $filter = $controller->after_controller_filter;
                break;
            case 'before_controller_load':
                $filter = $controller->before_controller_load_filter;
                break;
            default:
                trigger_error("filter type <i>$filter></i> not defined",  E_USER_ERROR); 
                return false;
        }
        
        if ($filter)
        { 
            if (is_array($filter))
            {
                $methods = $filter[0];
                if ($filter['only'] && $filter['except']) { trigger_error("Only and except are mutually exclusive for controller ".$controller->controller_name,  E_USER_ERROR); }

                if ($filter['only']){ $only = explode(',', $filter['only']); }
                if ($filter['except']){ $except = explode(',', $filter['except']); }
            }
            else
            {
                $methods = $filter;
            }

            $methods = explode(',', $methods);

            foreach($methods as $method_name)
            {
                $method_name = trim($method_name);
                
                #check if the method exists
                    if (!method_exists($controller, $method_name)) { trigger_error("Method \"<i>$method_name</i>\" does not exist for controller_filter in controller <i>$controller->controller_name</i>", E_USER_ERROR ); }
                
                #execute the method
                    if (!$only && !$except) { $controller->$method_name(); }
                    elseif ($only) { if (in_array($controller->controller_name, $only)) { $controller->$method_name(); } }
                    elseif ($except) { if (!in_array($controller->controller_name, $except)) { $controller->$method_name(); } }
            }
        }
    }

    function render_view($view_name = null)
    {
        if (!$view_name) { $view_name = App::$route['action']; }
        global $path_to_root;
        # set up the view_parameters
            global $view_parameters; if ($view_parameters) {foreach ($view_parameters as $variable => $value) { $$variable = $value; } }
        $view_url = $path_to_root."/".$this->face."/views/".$this->controller_name."/$view_name.php";
        #debug($view_url);
        require ($view_url);

        $this->has_rendered = true;
        return true;
    }

    function render_partial($partial_name)
    {
        $this->layout = null;
        $this->render_view('_'.$partial_name);
        $this->action_rendered_inline = true;
    }

    function execute_action($action_name = null)
    {
        if (!$action_name) { $action_name = App::$route['action']; }
        if (method_exists(App::$controller, $action_name) || method_exists(App::$controller, '__call'))
        {
            #debug('execute_action');
            if (isset(App::$route['id']) && App::$route['id'])
            {
                App::$controller->$action_name(App::$route['id']);
            }
            else
            {
                App::$controller->$action_name();
            }
        }

    }

    function render_inline() { $this->action_rendered_inline = true; }

    function __construct()
    {
        $controller_name = get_class($this);$controller_name = str_replace('_controller', '', $controller_name);
        $this->controller_name = $controller_name;

        #the layout
        #echo '<pre>';print_r($this);echo '</pre>';
        if (!isset($this->layout))
        {
            if (in_array($controller_name, $_SESSION[APP_NAME]['application']['layouts']))
            {
                $this->layout = $controller_name;
            }
        }
    }

}
/*
This is the order of execution for the application's controller flow:
- before_controller_load_filter (before the controller in that face is loaded. in face_controller)
- before_controller_execute_filter (before the controller in that face is called. in face_controller)
- before_controller_execute_filter (before the action / action in that controller is executed. in face_controller)
- before_filter (in controller)
- after_filter ( in controller)
- after_controller_filter (after controller executes its actions / renders the action. face controller)
*/
?>
