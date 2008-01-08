<?
class Environment {
    public $mail_send_method = 'sendmail';
    public $run_cron_jobs = true;

    function load($environment, $path_to_root) {
        #load this environment
        if ($environment == 'auto' | $environment == 'must_match')
        {
            #find_environments
            foreach (Environment::find_environments($path_to_root) as $env)
            {
                if (App::$reloading) {echo "<ul><li>Testing environment <i>$env</i></li>";}
                require_once($path_to_root."/config/environments/".$env.'.php');
                $env_object = new $env;
                if (isset($env_object->urls))
                {
                    if (App::$reloading) {echo "<ul>";}
                    foreach ($env_object->urls as $url)
                    {
                        if (App::$reloading) {echo "<li>testing match <i>$url</i></li>";}
                        if (preg_match($url, $_SERVER['SERVER_NAME']))
                        {
                            if (App::$reloading) {echo "<strong>Matched to environment <i>$env</i></strong>";}
                            $environment = $env;
                        }
                    }
                    if (App::$reloading) {echo "</ul>";}
                }
                if (App::$reloading) {echo "</ul>";}

            }
            if ($environment == "auto") # i.e. it's still auto
            {
                $environment = "development";
                if (App::$reloading) {echo "<li>No environment matched. Using <strong>development</strong> environment.</li>";}
            }
            if ($environment == "must_match")
            {
                $error = '<h1>Application Load Failed</h1>The domain <i>'.$_SERVER['HTTP_HOST'].'</i> could not be matched to any environment.';
                die($error);
            }
            if (App::$reloading) {echo "</ul>";}
        }
        require_once($path_to_root."/config/environments/".$environment.'.php'); $_SESSION[APP_NAME]['application']['environment'] = $environment;
        App::$env = new $environment;
    }

    function find_environments($path_to_root) {
        $environment = null;
        if (App::$reloading) {echo "<li>parsing environments folder</ul>"; }
        $check_path = $path_to_root.'/config/environments/';
        if ($handle = opendir($check_path))
        {
            while (false != ($file = readdir($handle)))
            {
                $environment = $file;
                $file = $check_path.$file;
                
                if (is_file($file))
                {
                    $environment = str_replace('.php', '', $environment);
                    $environments[] = $environment;
                }
            }
            closedir($handle);
            if (App::$reloading)
            {
                echo "<li>found environments:"; 
                echo '<pre>';print_r($environments);echo '</pre>';
                echo "<li>";
            }
        }
        if (App::$reloading) {echo "</ul></li>";}
        return $environments;
    }
}
class Env extends Environment { var $foo = ''; } #foo is there cos I read something about empty classes not working so lekker. yes, yes, where's my proof...
?>
