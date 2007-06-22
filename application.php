<?
class Application
{
    static $booting;static $reloading;
    static $models;
    static $env;
    static $default_face = null, $controller = null, $route = null;
    static $allowed_faces = array('cm', 'site', 'extranet');
    static $render_contents = null;

    function load_models()
    {
        if (App::$booting) {App::find_these('models', 'models');}

        foreach ($_SESSION[APP_NAME]['application']['models'] as $model)
        {
            if (App::$reloading) {echo "loading $model<br />"; }
            require_once($model);
        }
    }
    
    function init($path_to_root)
    {
    # check for application load or reload
        if (!isset($_SESSION[APP_NAME]['application'])) {App::$booting = true;}
        if ( isset($_GET['reload']) ){ App::$booting = true; App::$reloading = true; $_GET['reload'] = ''; }

        global $environment; #pulling it in from the config
        
        #slurp config/application.php settings
            global $default_face; if ($default_face) { App::$default_face = $default_face; }
            global $allowed_faces; if ($allowed_faces) { App::$allowed_faces = explode(',', $allowed_faces); }

        if (!App::$booting) {
            $environment = $_SESSION[APP_NAME]['application']['environment'];
        }
        Environment::load($environment, $path_to_root);
        App::load_models(); 

        if (App::$booting) {

            if (App::$reloading) {echo "Using face ".App::$default_face."<br />"; }

            App::find_these('layouts', App::$default_face.'/layouts');
            App::find_these('controllers', App::$default_face.'/controllers');
        }

    # if forced reload then print app variables and die
        if (App::$reloading)
        {
            print_r( $_SESSION );echo '<br />';
            print_r(App::$env);echo '<br />';

            die("Application reloaded");
        }
    }

    function find_these($name, $path)
    {
        if (App::$reloading) {echo "parsing $name folder<br />"; }
        #check if the dir exists
        if (!file_exists(App::$env->root.'/'.$path))
        {
            if (App::$reloading) {echo " $name folder not found<br />"; }
            return false;
        }

        if ($handle = opendir(App::$env->root.'/'.$path))
        {
            $files = Array();
            while (false != ($file_name = readdir($handle)))
            {
                $layout = $file_name;
                $file = App::$env->root.'/'.$path.'/'.$file_name;
                
                if (is_file($file))
                {
                    $files[$file_name] = $file;
                }
            }
            closedir($handle);
            $_SESSION[APP_NAME]['application'][$name] = $files;
        }
    }

    function require_controller($controller_name, $face = null)
    {
        if (isset($_SESSION[APP_NAME]['application']['controllers']) && in_array($controller_name.'.php', array_keys($_SESSION[APP_NAME]['application']['controllers'])))
        {
            require($_SESSION[APP_NAME]['application']['controllers'][$controller_name.'.php']);
            return true;
        }
        else
        {
            return false;
        }
    }

    function error_check($result, $die_on_error = true)
    {
        if (PEAR::isError($result) || MDB2::isError($result)) {
            if ($die_on_error)
            {
                die('<pre>'.$result->getMessage().' - '.$result->getUserinfo()).'</pre>';
            }
            else
            {
                return $result->code;
            }
        }
    }

}
#convenience method
class App extends Application { var $foo = ''; } #foo is there cos I read something about empty classes not working so lekker. yes, yes, where's my proof...

function build_route($path)
{
    #default route
    $result = array(
        'face' => '',
        'controller' => 'default_controller',
        'action' => '',
        'id' => ''
        );
    $result['face'] = App::$default_face;
        
    if ($path)
    {
        $path = split('/', $path);
        if (!in_array($path[0], App::$allowed_faces) && App::$default_face) #the first param is not a face, and we use default faces
        {
            $result['controller'] = $path[0].'_controller';

            if (isset($path[1])) { $result['action'] = $path[1]; }
            if (isset($path[2])) { $result['id'] = $path[2]; }
            
        }
        else
        {
            $result['face'] = $path[0];
            #verify the face
            if (!in_array($result['face'], App::$allowed_faces)) { trigger_error('Face <i>'.$result['face'].'</i> not found', E_USER_ERROR); }

            if ($path[1]) {$result['controller'] = $path[1].'_controller';}

            if (isset($path[2])) { $result['action'] = $path[2]; }
            if (isset($path[3])) { $result['id'] = $path[3]; }
        }
    }

    

    return $result;
}
/* todo move these methods into helper functions */

function href_to($path)
{
    $href = App::$env->url;

    $target = build_route($path);
    #echo "<!--";print_r($target);echo "-->";
    
    #route's are the same so just send bank emptystring
        if ($target['face'] == App::$route['face'] && $target['controller'] == App::$route['controller'] && $target['action'] == App::$route['action']) { return ''; }

    $href = App::$env->url.'/';
    #if the default face is the same as the target face leave the face out
        if ($target['face'] != App::$default_face) { $href .= $target['face'].'/'; }
    
    $href .= str_replace('_controller', '', $target['controller']);
    #no specified action?  let the controller decide
        if ($target['action'] != '') { $href .= '/'.$target['action']; }

    return $href;
}
?>
