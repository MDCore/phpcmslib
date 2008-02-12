<?
#todo clean up this file. messy
//error_reporting(E_ALL);
session_start();

if (!isset($path_to_root)) {$path_to_root = '.';}

/* load the application config */
require($path_to_root.'/config/application.php');

/* force test mode */
if (!defined('TEST_MODE')) {
    $environment = 'test';
}

/* 
 * PEAR in /vendor
 * If the shared-host doesn't install PEAR libraries, this adds the vendor/PEAR path
 * to include_path.
 */

if (isset($use_PEAR_in_vendor) && $use_PEAR_in_vendor) {
    $local_pear_path = $path_to_root.'/vendor/PEAR/pear/php/';
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$local_pear_path);
} 

/* PEAR Libraries */
require_once('MDB2.php');
if ((isset($uses_nested_set) && $uses_nested_set == true)) {
    require_once('DB/NestedSet.php');
}

/* core classes */
require("AR.php");
require("action_controller.php");
require("action_view_helpers.php");
require("environment.php");
require("application.php");
require('form_helpers.php');
require("asset_helpers.php");
require("ajax_helpers.php");

require("functions.php");

/* cm classes */
//todo: only load this on cm
require("cm_controller.php");
require("filter.php");
require("cm_paging.php");


/* extra vendor stuff */
require($path_to_root.'/vendor/phpmailer/class.phpmailer.php');

/*
 * only_require_libraries is used by the unit testing to prevent the application from starting (well... someday)
 */
if (!isset($only_require_libraries)) { 
    // before application init callback
    if (isset($before_application_start)) { $before_application_start(); }

    // application init
    App::init($path_to_root);

    // set the custom error handler
    if ($environment != '' && !defined('TEST_MODE')) {
        set_error_handler('framework_error_handler');
        set_exception_handler('framework_exception_handler');
    }


}
?>
