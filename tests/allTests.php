<?php
/* require new tests suites here */
require_once 'ARTest.php';
/* functions + helpers */
require_once 'functionsTest.php';
require_once 'stringHelpersTest.php';
require_once 'assetHelpersTest.php';
/* tasks */
require_once 'tasks/beachheadTest.php';
 
class allTests
{
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('Pedantic/Lib');
 
        /* add new test suites here */
        $suite->addTestSuite('ARTest');
        /* functions + helpers */
        $suite->addTestSuite('functionsTest');
        $suite->addTestSuite('stringHelpersTest');
        $suite->addTestSuite('assetHelpersTest');
        /* tasks */
        //$suite->addTestSuite('tasks_beachheadTest');
 
        return $suite;
    }
}
?>
