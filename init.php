<?
#todo clean up this file. messy
session_start();
require('MDB2.php');
require("functions.php");

/* core classes */
    require("AR.php");
    require("action_controller.php");
    require("action_view_helpers.php");
    require("environment.php");
    require("application.php");
    require('form_helpers.php');
    require("asset_helpers.php");
    require("ajax_helpers.php");

/* cm classes */
    require("cm_controller.php");
    require("filter.php");
    require("cm_paging.php");

#error_reporting(E_ALL);

if (!isset($path_to_root)) {$path_to_root = '.';}

#external libs
    require($path_to_root.'/vendor/phpmailer/class.phpmailer.php');

#load the application config
    require($path_to_root.'/config/application.php');
    
#before application init callback
    if (isset($before_application_start)) { $before_application_start(); }

#application init
    App::init($path_to_root);

?>
