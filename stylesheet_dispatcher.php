<?
require ($path_to_root.'/vendor/pedantic/lib/dispatcher.php');

class stylesheet_dispatcher extends dispatcher
{
    public function path_from_collection(&$collection)
    {
        $path = parent::path_from_collection(&$collection);
        $path = str_replace('_css', '.css', $path);
        return $path;
    }
    public function process(&$collection = null)
    {
        global $path_to_root;
        if (!$collection) { $collection = &$_GET; }
        $path = $this->path_from_collection($collection);

        #build the route
            App::$route = route_from_path($path);

        $stylesheet = $_GET['qa'];

        if (App::$route['face'] == '')
        {
            App::$face = App::$default_face;
        }
        else
        {
            App::$face = App::$route['face'];
        }

        $stylesheet = $path_to_root.'/'.App::$face.'/assets/stylesheets/'.$stylesheet;
        header('Content-type: text/css');
        require($stylesheet);
    }
}
?>
