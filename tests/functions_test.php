<?php
// Call functions_test::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) { define('PHPUnit_MAIN_METHOD', 'functions_test::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'MDB2.php';

require_once '../functions.php';

/**
 * Test class for functions.php.
 */
class functions_test extends PHPUnit_Framework_TestCase {
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */

    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('functions_test');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function __construct()
    {
    }
    public function __destruct()
    {
    }
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    }

    /*
     * test stuff that happens on construction
     */

    public function testSQLImplode1()
    {
        $input = array('SELECT' => 't.1, t.2', 'FROM' => 't');
        $expected = 'SELECT t.1, t.2 FROM t';

        $this->assertEquals($expected, SQL_implode($input));
    }
    public function testSQLImplode2()
    {
        $input = array(
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't'
        );
        $expected = 'SELECT t.1, t.2 FROM t';

        $this->assertEquals($expected, SQL_implode($input));
    }
    public function testSQLImplode3()
    {
        $input = array(
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't',
            'WHERE' => "t3='a'"
        );
        $expected = "SELECT t.1, t.2 FROM t WHERE t3='a'";

        $this->assertEquals($expected, SQL_implode($input));
    }
    public function testSQLImplode4()
    {
        $input = array(
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't',
            'WHERE' => "t3 = 'a'"
        );
        $expected = "SELECT t.1, t.2 FROM t WHERE t3 = 'a'";

        $this->assertEquals($expected, SQL_implode($input));
    }
    public function testSQLImplode5()
    {
        $input = array(
            'WHERE' => array("AND t4 = 'a'", " AND t43 = 'b'"),
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't'
        );
        $expected = "SELECT t.1, t.2 FROM t WHERE t4 = 'a' AND t43 = 'b'";

        $this->assertEquals($expected, SQL_implode($input));
    }
    public function testSQLImplode6()
    {
        $input = array(
            'WHERE' => array("t4 = 'a'", " AND t43 = 'b'"), /* note the subtle difference from above test */
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't'
        );
        $expected = "SELECT t.1, t.2 FROM t WHERE t4 = 'a' AND t43 = 'b'";

        $this->assertEquals($expected, SQL_implode($input));
    }
    public function testSQLImplode6a()
    {
        $input = array(
            'WHERE' => array("t4 = 'a'", "t43 = 'b'"),
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't'
        );
        $expected = "SELECT t.1, t.2 FROM t WHERE t4 = 'a' t43 = 'b'"; /* note: this is meant to be bad SQL! */

        $this->assertEquals($expected, SQL_implode($input));
    }
    /*
     * Test what happens when the phrase is prefixed in the string e.g. the WHERE phrase, as below
     */
    public function testSQLImplode7()
    {
        $input = array(
            'WHERE' => "WHERE t='a'",
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't'
        );
        $expected = "SELECT t.1, t.2 FROM t WHERE t='a'";

        $this->assertEquals($expected, SQL_implode($input));
    }


    public function testSQLExplode1()
    {
        $this->markTestIncomplete();
    }

    public function testSQLMerge1()
    {
        $input_1 = array(
            'WHERE' => array("w1 = 'a'", " AND w2 = 'b'"), 
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't'
        );

        $input_2 = array(
            'SELECT' => '*',
            'FROM' => 't',
            'ORDER BY' => 't.o'
        );
        $expected = array(
            'SELECT' => array('t.1', 't.2', '*'),
            'FROM' => array('t', 't'),
            'WHERE' => array("w1 = 'a'", " AND w2 = 'b'"),
            'ORDER BY' => array('t.o')
        );

        $this->assertEquals($expected, SQL_merge($input_1, $input_2));
    }
}

// Call functions_test::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'functions_test::main') {
    functions_test::main();
}
?>
