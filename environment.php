<?
class Environment
{
    public $content_path = '/cm';
    public $mail_send_method = 'sendmail';
    public $run_cron_jobs = true;

    function load($environment, $path_to_root)
    {
        #load this environment
        if ($environment == "auto")
        {
            #find_environments
            foreach (Environment::find_environments($path_to_root) as $env)
            {
                if (App::$reloading) {echo "Testing environment $env<br />";}
                require_once($path_to_root."/config/environments/".$env.'.php');
                $env_object = new $env;
                if (isset($env_object->urls))
                {
                    foreach ($env_object->urls as $url)
                    {
                        if (App::$reloading) {echo "testing match $url<br />";}
                        if (preg_match($url, $_SERVER['SERVER_NAME']))
                        {
                            if (App::$reloading) {echo "Matched to $env environment<br />";}
                            $environment = $env;
                        }
                    }
                }
            }
            if ($environment == "auto")
            {
                $environment = "staging";
            }
        }
        require_once($path_to_root."/config/environments/".$environment.'.php'); $_SESSION[APP_NAME]['application']['environment'] = $environment;
        App::$env = new $environment;
        App::$env->content_path = App::$env->root.App::$env->content_path;
    }
    function find_environments($path_to_root)
    {
        if (App::$reloading) {echo "parsing environments folder<br />"; }
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
            if (App::$reloading) {
                echo "found environments <br />"; 
                print_r($environments);
            }
            return $environments;echo 'br';
        }
    }
}
class Env extends Environment { var $foo = ''; } #foo is there cos I read something about empty classes not working so lekker. yes, yes, where's my proof...
?>
