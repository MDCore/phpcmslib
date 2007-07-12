<?
class Application
{
    static $booting;static $reloading;
    static $models;
    static $schema_definition = null;
    static $env;
    static $default_face = null, $controller = null, $route = null;
    static $allowed_faces = array('cm', 'site', 'extranet');
    static $render_contents = null;

    function init($path_to_root)
    {

    # check for application load or reload
        if (!isset($_SESSION[APP_NAME]['application'])) {App::$booting = true;}
        if ( isset($_GET['reload']) )
        {
            App::$booting = true; App::$reloading = true; $_GET['reload'] = '';
?><style type="text/css"> li { font-family: "Courier New",monospace,verdana,arial; } </style><ol><?
        }

        global $environment; #pull in the environment rom the config
        
        # check for running from shell, for tests
        if (isset($_SERVER['SHELL']) && !is_null($_SERVER['SHELL']))
        {
            #todo, fix this hack
            $environment = 'development';    
        }
        
        #slurp config/application.php settings
            global $default_face; if ($default_face) { App::$default_face = $default_face; }
            global $allowed_faces; if ($allowed_faces) { App::$allowed_faces = explode(',', $allowed_faces); }

        if (!App::$booting)
        {
            $environment = $_SESSION[APP_NAME]['application']['environment'];
        }

        Environment::load($environment, $path_to_root);
 
        #load the schema definition
            require ($path_to_root.'/config/cache/schema_definition.php');
            if (!isset($schema_definition) || $schema_definition == null) { trigger_error('Schema definition not set', E_USER_ERROR);  }
            App::$schema_definition = $schema_definition;

        App::load_models(); 

        if (App::$booting) {

            if (App::$reloading) {echo "<li>Using face <strong>".App::$default_face."</strong></li>"; }

            App::find_these('layouts', App::$default_face.'/layouts');
            App::find_these('controllers', App::$default_face.'/controllers');

    #run cron jobs, only on app start, not each page load!
            if (class_exists('cron_job'))
            {
                require($path_to_root.'/cron_jobs/auto_mailer.php');
                if (App::$env->run_cron_jobs)
                {
                    $cron_job = new cron_job;
                    $cron_job->run_all_jobs();
                }
            }
        }

    # if forced reload then print app variables and die
        if (App::$reloading)
        {
            echo "<li>Session:<pre>";print_r($_SESSION);echo '</pre></li>';
            echo "<li>App::\$env<pre>";print_r(App::$env);echo '</pre></li>';
            echo "<li>Application reloaded</li>";
            echo "</ol>";
            die();
        }
    }

    function load_models()
    {
        if (App::$booting) {App::find_these('models', 'models');}

        if (App::$reloading) {echo "<li>Loading models<ul>"; }
        foreach ($_SESSION[APP_NAME]['application']['models'] as $model_name => $model)
        {
            if (App::$reloading) {echo "<li>loading <strong>$model_name</strong> ($model)</li>"; }
            require_once($model);
        }
        if (App::$reloading) {echo "</ul></li>"; }
    }
    
    function find_these($name, $path)
    {
        if (App::$reloading) {echo "<li>parsing $name folder</li>"; }
        #
        #check if the dir exists
        if (!file_exists(App::$env->root.'/'.$path))
        {
            if (App::$reloading) {echo "<li>$name folder not found</li>"; }
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

    function require_this($type_name, $name)
    {
        $type_name = pluralize($type_name);
        if (isset($_SESSION[APP_NAME]['application'][$type_name]) && in_array($name.'.php', array_keys($_SESSION[APP_NAME]['application'][$type_name])))
        {
            #echo '<pre>';print_r($_SESSION[APP_NAME]['application'][$type_name][$name.'.php']);echo '</pre>';
            $file_to_require = $_SESSION[APP_NAME]['application'][$type_name][$name.'.php'];
            return $file_to_require;
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

?>
