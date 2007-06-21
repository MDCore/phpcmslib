<?
class action_controller
{
    public $has_rendered = false;
    public $layout = 'application';
    public $face = "site";

    public $before_view_filter = null, $after_view_filter = null;
    public $before_controller_filter = null, $before_controller_execute_filter = null, $after_controller_filter;
    /*
     * these filters work like so:
     * > $filter = array('method_name', 'only' => 'view, view_2, view3', 'except' => 'view_4');
     * OR
     * > $filter = 'method_name;
     *
     * only will only execute that method for the specified views / controllers.
     * except will not execute that method for the specified views / controllers, but will for all others.
     * only and except are mutually exclusive. Using both will cause an error.
     */

    /*function __call($method_name, $params)
    {
        return $this->render_view($method_name);
    }*/

    public function handle_controller_filter($filter, $controller_name)
    {
        $only = $except = null;

        /* this method handles controller filter method execution */
        if ($filter)
        { 
            if (is_array($filter))
            {
                $method_name = $filter[0];
                if ($filter['only'] && $filter['except']) { trigger_error('Only and except using for filter',  E_USER_ERROR); } #todo better error name

                if ($filter['only']){ $only = explode(',', $filter['only']); }
                if ($filter['except']){ $except = explode(',', $filter['except']); }
            }
            else
            {
                $method_name = $filter;
            }

            #check if the method exists
                if (!method_exists($this, $method_name)) { trigger_error('Method '. $method_name . ' does not exist for filter.', E_USER_ERROR ); } # todo better error name

            #execute the method
            if (!$only && !$except) { $this->$method_name(); }
            elseif ($only) { if (in_array($controller_name, $only)) { $this->method_name(); } }
            elseif ($except) { if (!in_array($controller_name, $only)) { $this->method_name(); } }
        }
    }

    function render_view($view_name)
    {
        global $path_to_root;
        $this->has_rendered = true;
        $view_url = $path_to_root."/".$this->face."/views/".$this->controller_name."/$view_name.php";
        #debug($view_url);
        require ($view_url);
        return true;
    }

    function __construct()
    {
        $controller_name = get_class($this);$controller_name = str_replace('_controller', '', $controller_name);
        $this->controller_name = $controller_name;

        #the layout
        if (!$this->layout)
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
- before_controller_filter (before the controller in that face is loaded or called. in face_controller)
- before_controller_execute_filter (before the view / action in that controller is executed. in face_controller)
- before_filter (in controller)
- {view / action } (in controller)
- after_filter ( in controller)
- after_controller_filter (after controller executes its actions / renders the view. face controller)
*/
?>
