<?
/*
 * TODO
 * - remove render_partial. Partials are second class? why not just use views?
 * - change render_as_string to instantiate a new controller object instead of mucking with the current one
 */

class action_controller {
    public $has_rendered = false;
    //public $face = "", $controller = null, $view = null;
    public $route = null; /* the fully qualified route. Nothing is assumed here. face, controller, action, layout. */
    public $layout = null;/* layout still needs to be independant */
    public $virtual = false;
    public $rendered_content = null; /* todo should this be private */

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
    public $before_action_filter = null, $after_action_filter = null;
    public $before_controller_load_filter = null, $before_controller_execute_filter = null, $after_controller_filter = null;

    public function handle_controller_filter($filter, $controller = null) {
        /*
         * face controllers execute controller-level filters on their child controllers by default.
         *  That is why the filters are defined in the face controller but executed on the current controller.
         *  The exception to this is the before controller load filter, which is executed on the face_controller,
         *  since we may want to switch controllers
         */

        if ($controller == null) {
            $controller = $this;
        }

        $only = $except = null;

        switch ($filter) {
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

        if ($filter) {
            if (is_array($filter)) {
                $methods = $filter[0];
                if ($filter['only'] && $filter['except']) { trigger_error("Only and except are mutually exclusive for controller ".$controller->route['controller'],  E_USER_ERROR); }

                if ($filter['only']){ $only = explode(',', $filter['only']); }
                if ($filter['except']){ $except = explode(',', $filter['except']); }
            } else {
                $methods = $filter;
            }

            $methods = explode(',', $methods);

            foreach($methods as $method_name) {
                $method_name = trim($method_name);

                /* check if the method exists */
                if (!method_exists($controller, $method_name)) { trigger_error('Method "<i>$method_name</i>" does not exist for controller_filter in controller <i>'.$controller->route['controller'].'</i>', E_USER_ERROR ); }

                /* execute the method */
                if (!$only && !$except) { $controller->$method_name(); }
                elseif ($only) { if (in_array($controller->route['controller'], $only)) { $controller->$method_name(); } }
                elseif ($except) { if (!in_array($controller->route['controller'], $except)) { $controller->$method_name(); } }
            }
        }
    }
    public function render($route_param = null) {
      $this->route = $this->parse_route_parameter($route_param);

        /* if the $route_param is JUST a view then execute that method too. That way one
         * can just call render->(view_name) and it will execute the action and render the action's view
         */
        if (!is_array($route_param) && !is_null($route_param)) {
            $this->$route_param();
        }

        if (isset($this->layout) && $this->layout) {
            $this->render_layout();
        }
        else {
            $this->render_content(); #no layout to call render_content for itself.. so this effectively means "render without a layout"
        }
    }
    public function render_as_string($url_as_array, $layout = null)
    {
        /* save some settings */
        $current_route = $this->route;

        $current_rendered_status = $this->action_rendered_inline;

        /* overwrite individual items, that way current ways stay the same e.g. the face */
        foreach ($url_as_array as $url_portion => $url_value) {
            App::$route[$url_portion] = $url_value;
        }
        foreach ($url_as_array as $part => $value) {
            $this->route[$part] = $value;
        }
        /* $this->layout still needs to be independant */
        $this->layout = $this->route['layout'] = $layout;

        /* deal with $_GET */
        if (isset($url_as_array['GET'])) {
            $current_GET = $_GET;
            $_GET = $url_as_array['GET'];
        }

        $this->action_rendered_inline = false;

        /* execute the action, saving the contents */
        ob_start();
        $this->execute_action(null, true);
        $this->render_contents = ob_get_contents();
        ob_clean();

        /* render the layout (if applicable) and view */
        ob_start();
        $this->render();
        $result = ob_get_contents();
        ob_clean();

        /* restore settings */
        $this->route = $current_route;
        $this->layout = $current_route['layout'];

        $this->action_rendered_inline = $current_rendered_status;
        if (isset($current_GET)) { $_GET = $current_GET; }

        return $result;
    }
    function render_content() {
        if (isset($this->action_rendered_inline) && $this->action_rendered_inline) {
            echo $this->render_contents; #dump the action rendered content
        }
        else { #render the view file
            $this->render_view();
        }

        $this->has_rendered = true;
    }
    function render_layout() {
        if ($this->view_parameters) {foreach ($this->view_parameters as $variable => $value) { $$variable = $value; } }

        if ($layout_path = App::require_this('layout', $this->layout)) {
            require ($layout_path);
        } else {
            /* fail: layout not found */
            $environment = $_SESSION[APP_NAME]['application']['environment'];
            if ($environment == 'production') {
                http_header(404, true);
            } else {
                trigger_error('Layout <i>'.$this->layout.'</i> not found', E_USER_ERROR);
            }
        }

    }
    function render_view($route_param = null) {
        $route = $this->parse_route_parameter($route_param); // allow passing in a different route collection or view to render

        global $path_to_root;

        # set up the view_parameters
            if ($this->view_parameters) {foreach ($this->view_parameters as $variable => $value) { $$variable = $value; } }

        $view_url = $path_to_root.'/'.$route['face'].'/views/'.$route['controller'].'/'.$route['action'].'.php';
        //debug($view_url);
        if (file_exists($view_url)) {
            include($view_url);
        }
        else {
            $environment = $_SESSION[APP_NAME]['application']['environment'];
            if ($environment == 'production') {
                http_header(404, true);
            } else {
                trigger_error("View <i>".$route['face'].'/'.$route['controller'].'/'.$route['action']."</i> not found", E_USER_ERROR);
            }
        }

        return true;
    }
    function render_partial($partial_name, $collection = null) {
        $this->layout = null;

        # set up the view_parameters
            if ($this->view_parameters) {foreach ($this->view_parameters as $variable => $value) { $$variable = $value; } }

        if (!$collection) { $this->render_view('_'.$partial_name); }
        else {
            $counter = 0;
            $item_name = array_keys($collection);
            $item_name = $item_name[0];
            foreach($collection[$item_name] as $collection_item) {
                $counter++;
                $this->view_parameters['counter'] = $counter;
                $this->view_parameters[$item_name] = $collection_item;
                $this->render_view('_'.$partial_name);
            }
        }
        #$this->render_inline();
    }
    function execute_action($action_name = null, $ignore_ajax_request_settings = false) {
        if (!$action_name) { $action_name = App::$route['action']; }
        if (method_exists($this, $action_name) || method_exists($this, '__call')) {
            # check for ajax requests, and automatically set render_inline and layout = null
            /* todo check that this still works. It seems to have mysteriously stopped working recently */
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' && !$ignore_ajax_request_settings)
            {
                $this->request_type = 'ajax';
                $this->layout = null; $this->render_inline();
            }
            #debug('execute_action');
            if (isset($this->route['id']) && $this->route['id']) {
                $this->$action_name($this->route['id']);
            }
            else {
                $this->$action_name();
            }
        }
        else {
            #raise an exception!
        }

    }

    private function parse_route_parameter($route_param = null) {
        $route = $this->route;
        $route['action'] = null; /* we check later on that route is explicitly set */

        if (is_array($route_param)) {
            /* route param might be called with an ACTION or a VIEW. Convert VIEW to ACTION (there's a life lesson right there.) */
            if (isset($route_param['view'])) {
                $route_param['action'] = $route_param['view'];
                unset($route_param['view']);
            }

            /* it is an array so overwrite all the array options of the route with the ones in the
             * passed route. e.g.if I'm passing array('controller' => 'customers') set the route's
             * controller to customers instead of the default
             */
            foreach ($route_param as $route_part => $value) {
                $route[$route_part] = $route_param[$route_part];
            }
        }
        elseif (!is_null($route_param)) {
            /* if a string is passed it is a view to be rendered in the current controller */
            $route['action'] = $route_param;
        }
        elseif ($this->route['action'] != null) {
            $route['action'] = $this->route['action'];
        }
        else {
            /*  I'm only setting the view here and not in the route initialization because either
             *  you use the default route or you pass in a partial route _including_ a view. If you
             *  pass in a partial route without a view it's likely to be a  mistake: you are
             *  expecting the app::routes' action to be used. Obviously if there is an important,
             *  useful, logical case to be made against this then we change this.
             */
            $route['action'] = App::$route['action'];
        }

        if (!isset($route['action'])) {
            trigger_error('no action to render',  E_USER_ERROR); die();
        }

        return $route;
    }

    /*
     * this method essentialy means: don't try and load a view file, I'm rendering all the content inside this method
     */
    function render_inline() {
        $this->action_rendered_inline = true;
    }

    function __construct() {
        /* determine the controller name */
        $controller_name = get_class($this); $controller_name = str_replace('_controller', '', $controller_name);

        /* choose the layout */
            if (!isset($this->layout)) {
                /*
                 * We are referring to the route's face controller and not app::$face here
                 * because this construct() call happens before the face and controller
                 * are set in stone, so to speak
                 */
                if (in_array($controller_name, $_SESSION[APP_NAME]['application'][App::$route['face']]['layouts'])) {
                    $this->layout = $controller_name;
                }
            }

        /* set up the route */
        $this->route = array(
            'face'          => App::$route['face'],
            'controller'    => $controller_name,
            'action'        => App::$route['action'],
            'id'            => App::$route['id'],
            'layout'        => $this->layout
        );

    }

}
?>
