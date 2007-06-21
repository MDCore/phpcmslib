<?
#todo clean up this file. messy
session_start();
require_once('MDB2.php');
require_once("functions.php");

/* core classes */
    require_once("AR.php");
    require_once("action_controller.php");
    require_once("environment.php");
    require_once("application.php");

/* cm classes */
    require_once("cm_controller.php");
    require_once("filter.php");

#error_reporting(E_ALL);

if (!isset($path_to_root)) {$path_to_root = '.';}

#external libs
    require($path_to_root.'/vendor/phpmailer/class.phpmailer.php');

#  load the application config
    require($path_to_root.'/config/application.php');

#application init
    App::init($path_to_root);

    if (App::$booting)
    {
        require($path_to_root.'/cron_jobs/auto_mailer.php');
        if (App::$env->run_cron_jobs)
        {
        #run cron jobs, only on app start, not each page load!
            $cron_job = new cron_job;
            $cron_job->run_all_jobs();
        }
    }
?>
