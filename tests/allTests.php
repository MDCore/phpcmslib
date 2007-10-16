<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'allTests::main');
}
 
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';


#homemade mocking stuffs
require_once 'mocks/application.php';

require_once 'ARTest.php';
require_once 'functions_test.php';
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
        $suite->addTestSuite('asset_helpers_test');
        #add new test suites here
 
        return $suite;
    }
}
 
if (PHPUnit_MAIN_METHOD == 'allTests::main') {
    allTests::main();
}
?>
