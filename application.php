<?
class Application {
    static $booting;static $reloading;
    static $models;
    static $schema_definition = null;
    static $env;
    static $route = null, $face = null, $controller = null;
    static $default_face = null, $allowed_faces = array('cm', 'site', 'extranet');
    static $render_contents = null;
    static $skip_model_require = false;

    static function init($path_to_root) {

        /* check for application load or reload */
        if (!isset($_SESSION[APP_NAME]['application'])) {App::$booting = true;}
        if ( isset($_GET['reload']) ) {
            App::$booting = true; App::$reloading = true; $_GET['reload'] = '';
            ?><style type="text/css"> li { font-family: "Courier New",monospace,verdana,arial; } </style><ol><?
        }

        global $environment; #pull in the environment from the config
        
        if (defined('TEST_MODE')) {
            $environment = 'test';
        }
        
        /* slurp config/application.php settings */
            global $default_face; if ($default_face) { App::$default_face = $default_face; }
            global $allowed_faces; if ($allowed_faces) { App::$allowed_faces = explode(',', $allowed_faces); }

        if (!App::$booting) {
            $environment = $_SESSION[APP_NAME]['application']['environment'];
        }
        Environment::load($environment, $path_to_root);

        /* load the schema definition */
        include $path_to_root.'/config/cache/schema_definition.php';
        if (!isset($schema_definition) || $schema_definition == null) { trigger_error('Schema definition not set', E_USER_WARNING);  }
        App::$schema_definition = $schema_definition;

        App::load_models($path_to_root); 

        /* load the layouts, controllers and views for ALL faces */
        if (App::$booting) {
            if (App::$reloading) { echo "<li>Using face <strong>".App::$default_face."</strong></li>"; }

            foreach (App::$allowed_faces as $face) {
                App::find_these('layouts', $face);
                App::find_these('controllers', $face);
            }

            /* run cron jobs, only on app boot, not each page load! */
            if (class_exists('cron_job')) {
                require($path_to_root.'/cron_jobs/auto_mailer.php');
                if (App::$env->run_cron_jobs)
                {
                    $cron_job = new cron_job;
                    $cron_job->run_all_jobs();
                }
            }
        }

        /* if forced reload then print app variables and die */
        if (App::$reloading) {
            echo "<li>Session:<pre>";print_r($_SESSION[APP_NAME]);echo '</pre></li>';
            echo "<li>App::\$env<pre>";print_r(App::$env);echo '</pre></li>';
            echo "<li>Application reloaded</li>";
            echo "</ol>";
            die();
        }
    }

    static function load_models($path_to_root) {
        if (App::$booting) { App::find_these('models'); }

        if (App::$reloading) { echo "<li>Loading models<ul>"; }
        if (isset($_SESSION[APP_NAME]['application']['models'])) {
            foreach ($_SESSION[APP_NAME]['application']['models'] as $model_name => $model) {
                if (App::$reloading) {echo "<li>loading <strong>$model_name</strong> ($model)</li>"; }
                if (!App::$skip_model_require) { require($model); }
            }
        }
        else {
            if (App::$reloading) { echo "<li>No models found.</li>"; }
        }
        if (App::$reloading) { echo "</ul></li>"; }
    }
    
    static function find_these($name, $face = null, $path = null) {
        if (App::$reloading) {
            echo "<li>parsing $name folder";
            if ($face) { echo " for $face"; }
            echo "</li>";
        }
        #build the path if it has not been passed
            if (!$path && $face) { $path = $face.'/'.$name; } elseif (!$path && !$face) { $path = $name; }

        #check if the dir exists
        if (!file_exists(App::$env->root.'/'.$path)) {
            if (App::$reloading) { echo "<li>$name folder not found (".App::$env->root.'/'.$path.")</li>"; }
            return false;
        }

        if ($handle = opendir(App::$env->root.'/'.$path)) {
            $files = Array();
            while (false != ($file_name = readdir($handle))) {
                $layout = $file_name;
                $file = App::$env->root.'/'.$path.'/'.$file_name;
                
                if (is_file($file)) {
                    $files[$file_name] = $file;
                }
            }
            ksort($files); // oh my WORD... what a hack. we need model load prioritization TODO
            closedir($handle);

            if ($face) {
                $_SESSION[APP_NAME]['application'][$face][$name] = $files;
            }
            else {
                $_SESSION[APP_NAME]['application'][$name] = $files;
            }
        }
    }

    static function require_this($type_name, $name, $face = null) {
        #this is not used to require models, only other resources

        $type_name = pluralize($type_name);

        if (!$face) { $face = App::$face; }
        
        if (isset($_SESSION[APP_NAME]['application'][$face][$type_name]) && in_array($name.'.php', array_keys($_SESSION[APP_NAME]['application'][$face][$type_name]))) {
            #echo '<pre>';print_r($_SESSION[APP_NAME]['application'][$face][$type_name][$name.'.php']);echo '</pre>';
            $file_to_require = $_SESSION[APP_NAME]['application'][$face][$type_name][$name.'.php'];
            return $file_to_require;
        }
        else {
            return false;
        }
    }

    static function error_check($result, $die_on_error = true) {
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


/**
 * error handler only for !dev
 */

function framework_error_handler($errno, $errstr='', $errfile='', $errline='') {
    // if error has been supressed with an @
    if (error_reporting() == 0) {
        return;
    }

    if(!defined('E_STRICT')) {
        define('E_STRICT', 2048);
    }
    if(!defined('E_RECOVERABLE_ERROR')) {
        define('E_RECOVERABLE_ERROR', 4096);
    }
    /* I don't want these errors to be dealt with... they're annoying ones :) */
    switch($errno) {
    case E_STRICT:
    case E_NOTICE:
    case E_USER_NOTICE:
        return true; // false means: let the default error handler take care of it
        break;
        /*
    case E_ERROR:
    case E_WARNING:
    case E_PARSE:
    case E_CORE_ERROR:
    case E_CORE_WARNING:
    case E_COMPILE_ERROR:
    case E_COMPILE_WARNING:
    case E_USER_ERROR:
    case E_USER_WARNING:
    case E_RECOVERABLE_ERROR:
        */
    }

    $backtrace = array_reverse(debug_backtrace());

    handle_error($errno, $errstr, $errfile, $errline, $backtrace);
}
function framework_exception_handler($exc) {
    $errno = $exc->getCode();
    $errstr = $exc->getMessage();
    $errfile = $exc->getFile();
    $errline = $exc->getLine();

    $backtrace = $exc->getTrace();

    handle_error($errno, $errstr, $errfile, $errline, $backtrace);
}

function handle_error($errno, $errstr='', $errfile='', $errline='', $backtrace = null) {
    /* Lets 500 - Internal Server Error this script */
    http_header('500');

    $detailed_error = detailed_error_description($errno, $errstr, $errfile, $errline, $backtrace);

    /* do we send an email or just display it? */
    global $email_errors_to;
    global $environment;
    if (isset($email_errors_to) && $email_errors_to != '' && $environment != 'development') {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

        $subject = '['.$_SERVER['HTTP_HOST'].'] Error - '.date(DATE_RFC822);
        mail($email_errors_to, $subject, $detailed_error, $headers);

        // print a pretty error message 
        die(friendly_error_description());
    } else {
        //die(friendly_error_description());
        die($detailed_error);
    }
}

function detailed_error_description($errno, $errstr='', $errfile='', $errline='', $backtrace = null) {
    /* stringify and friendlify error codes */
    switch($errno) {
    case E_ERROR:
        $error_friendly = "Error";
        $error_type = 'E_ERROR';
        break;
    case E_WARNING:
        $error_friendly = "Warning";
        $error_type = 'E_WARNING';
        break;
    case E_PARSE:
        $error_friendly = "Parse Error";
        $error_type = 'E_PARSE';
        break;
    case E_NOTICE:
        $error_friendly = "Notice";
        $error_type = 'E_NOTICE';
        break;
    case E_CORE_ERROR:
        $error_friendly = "Core Error";
        $error_type = 'E_CORE_ERROR';
        break;
    case E_CORE_WARNING:
        $error_friendly = "Core Warning";
        $error_type = 'E_CORE_WARNING';
        break;
    case E_COMPILE_ERROR:
        $error_friendly = "Compile Error";
        $error_type = 'E_COMPILE_ERROR';
        break;
    case E_COMPILE_WARNING:
        $error_friendly = "Compile Warning";
        $error_type = 'E_COMPILE_WARNING';
        break;
    case E_USER_ERROR:
        $error_friendly = "User Error";
        $error_type = 'E_USER_ERROR';
        break;
    case E_USER_WARNING:
        $error_friendly = "User Warning";
        $error_type = 'E_USER_WARNING';
        break;
    case E_USER_NOTICE:
        $error_friendly = "User Notice";
        $error_type = 'E_USER_NOTICE';
        break;
    case E_STRICT:
        $error_friendly = "Strict Notice";
        $error_type = 'E_STRICT';
        break;
    case E_RECOVERABLE_ERROR:
        $error_friendly = "Recoverable Error";
        $error_type = 'E_RECOVERABLE_ERROR';
        break;
    default:
        $error_type = "Unknown error ($errno)";
        break;
    }

    /* backtrace does echo()'s and print_r()'s so grab it! */
    ob_start();
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <title><?=APP_DISPLAY_NAME;?></title>
<style type="text/css">
.error_handler_part {
    border: 2px solid #999;
    background-color: #ffffcc;
    padding: 5px;
    margin-bottom: 5px;
}
.error_handler_part h3 {
    margin: 0; padding: 0;
}
</style>
    </head>
    <body>
    <h2><?="$error_friendly ($error_type)";?></h2> in <strong><?=$errfile;?></strong> on line <strong><?=$errline;?></strong><pre><?=$errstr;?></pre><?

    if (isset($backtrace) && !is_null($backtrace)) {
        backtrace($backtrace);
    }

    /* $_GET */
    echo '<div class="error_handler_part"><h3>$_GET</h3><pre>';print_r($_GET);echo '</pre></div>';

    /* $_POST */
    echo '<div class="error_handler_part"><h3>$_POST</h3><pre>';print_r($_POST);echo '</pre></div>';


    /* $_SERVER */
    echo '<div class="error_handler_part"><h3>$_SERVER</h3><pre>';print_r($_SERVER);echo '</pre></div>';

    ?></body></html><?

    $error_page = ob_get_contents();
    ob_clean();
    return $error_page;
}
function friendly_error_description() {
    ob_start();
    ?><html>
        <head></head>
        <body>
        <h1>Oops!</h1>
        Something went wrong that we didn't expect and unfortunately we can't carry on.<br/><br/>
        Don't worry, we've sent an email to the system administrator, so someone does know about it.
        </body>
        </html>
    <?
    $error_page = ob_get_contents();
    ob_clean();
    return $error_page;
}

function backtrace($backtrace = null) {
    if (!is_null($backtrace)) {
        $bt = $backtrace;
    } else {
        $bt = debug_backtrace();
    }
   
    echo '<div class="error_handler_part"><h3>Backtrace (most recent call last)</h3>';

    for($i = 0; $i <= count($bt)- 1; $i++) {
        if(!isset($bt[$i]["file"])) {
            echo "[PHP core called function]<br />";
        } else {
            echo "File: <strong>".$bt[$i]["file"]."</strong><br/>";
        }
       
        if(isset($bt[$i]["line"])) {
            echo "<strong>line ".$bt[$i]["line"]."</strong><br />";
        }
        echo "function called: <i>".$bt[$i]["function"].'</i><br />';
       
        if($bt[$i]["args"]) {
            echo "args: ";
            for($j = 0; $j <= count($bt[$i]["args"]) - 1; $j++) {
                if(is_array($bt[$i]["args"][$j])) {
                    echo '<pre>';print_r($bt[$i]["args"][$j]); echo '</pre>';
                } else {
                    echo $bt[$i]["args"][$j];
                }
                           
                if($j != count($bt[$i]["args"]) - 1) {
                    echo ", ";
                }
            }
        }
        echo '<hr/>';
    }
    echo '</div>';
}
?>
