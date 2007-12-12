<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'allTests::main');
}
$path_to_root = '../../../..';

$test_with_vendor_PEAR = true;

if ($test_with_vendor_PEAR) {
    $local_pear_path = $path_to_root.'/vendor/PEAR/pear/php/';
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$local_pear_path);
}

require_once 'MDB2.php' ;
require_once 'DB/NestedSet.php' ;
 
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/* get the working #todo
$only_require_libraries = true;
require '../init.php';
 */
require_once '../AR.php';
require_once '../functions.php';
require_once '../asset_helpers.php';

#homemade mocking stuffs
require_once 'mocks/application.php';

require_once 'ARTest.php';
require_once 'functions_test.php';
require_once 'string_helpers_test.php';
require_once 'asset_helpers_test.php';
# include new tests suites here
 
class allTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
 
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Pedantic/Lib');
 
        $suite->addTestSuite('ARTest');
        $suite->addTestSuite('functions_test');
        $suite->addTestSuite('string_helpers_test');
        $suite->addTestSuite('asset_helpers_test');
        #add new test suites here
 
        return $suite;
    }
}
 
if (PHPUnit_MAIN_METHOD == 'allTests::main') {
    allTests::main();
}
?>
