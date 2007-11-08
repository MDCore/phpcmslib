<?php
// Call functions_test::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) { define('PHPUnit_MAIN_METHOD', 'functions_test::main');
}

require_once 'PHPUnit/Framework.php';

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
    public function testSQLImplode5a()
    {
        $input = array(
            'WHERE' => array("t4 = 'a'", " AND t43 = 'b'"), /* note the subtle difference from above test */
            'SELECT' => array('t.1', 't.2'),
            'FROM' => 't'
        );
        $expected = "SELECT t.1, t.2 FROM t WHERE t4 = 'a' AND t43 = 'b'";

        $this->assertEquals($expected, SQL_implode($input));
    }
    public function testSQLImplode6()
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
        $input = "SELECT * FROM t";
        $expected = array(
            "SELECT"    => '*',
            "FROM"      => 't'
        );
        $this->assertEquals($expected, SQL_explode($input));
    }
    public function testSQLExplode2()
    {
        $input = "SELECT a, b as cats, c FROM t INNER JOIN bob";
        $expected = array(
            "SELECT"    => array('a', 'b as cats', 'c'),
            "FROM"      => array('t INNER JOIN bob'),
        );
        $this->assertEquals($expected, SQL_explode($input));
    }
    public function testSQLExplode3()
    {
        $input = "SELECT a, b as cats, c FROM t INNER JOIN bob ON bob.a = t.p WHERE id=3 AND (t='a' OR t='b') ORDER BY p ASC";
        $expected = array(
            "SELECT"    => array('a', 'b as cats', 'c'),
            "FROM"      => array('t INNER JOIN bob ON bob.a = t.p'),
            "WHERE"     => array("id=3 AND (t='a' OR t='b')"),
            "ORDER BY"  => array('p ASC'),
        );
        $this->assertEquals($expected, SQL_explode($input));
    }
    
    public function testSQLExplode4()
    {
        $input = "SELECT a, b as cats, c FROM t INNER JOIN bob ON bob.a = t.p WHERE id=3 AND (t='a' AND t='b') ORDER BY p ASC";
        $expected = array(
            "SELECT"    => array('a', 'b as cats', 'c'),
            "FROM"      => array('t INNER JOIN bob ON bob.a = t.p'),
            "WHERE"     => array("id=3 AND (t='a' AND t='b')"),
            "ORDER BY"  => array('p ASC'),
        );
        $this->assertEquals($expected, SQL_explode($input));
    }

    public function testSQLExplode5()
    {
        $input = "SELECT a, b as cats, sum(c) as ctotal FROM t INNER JOIN bob ON bob.a = t.p WHERE id=3 AND (t='a' AND t='b') GROUP BY sum(c) ORDER BY p ASC";
        $expected = array(
            "SELECT"    => array('a', 'b as cats', 'sum(c) as ctotal'),
            "FROM"      => array('t INNER JOIN bob ON bob.a = t.p'),
            "WHERE"     => array("id=3 AND (t='a' AND t='b')"),
            "ORDER BY"  => array('p ASC'),
            "GROUP BY"  => array('sum(c)'),
        );
        $this->assertEquals($expected, SQL_explode($input));
    }
    public function testSQLExplode6()
    {
        $input = "SELECT a, b as cats, (SELECT ID FROM bob d WHERE d.id = t.id) as pha FROM t INNER JOIN bob ON bob.a = t.p WHERE id=3 AND (t='a' AND t='b') GROUP BY sum(c) ORDER BY p ASC";
        $expected = array(
            "SELECT"    => array('a', 'b as cats', '(SELECT ID FROM bob d WHERE d.id = t.id) as pha'),
            "FROM"      => array('t INNER JOIN bob ON bob.a = t.p'),
            "WHERE"     => array("id=3 AND (t='a' AND t='b')"),
            "ORDER BY"  => array('p ASC'),
            "GROUP BY"  => array('sum(c)'),
        );
        #$this->assertEquals($expected, SQL_explode($input));
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

    public function testImplodeWithKeys()
    {
        $this->markTestIncomplete();
    }

    public function testSplitAliasedString()
    {
        $this->markTestIncomplete();
    }

    public function testPageParameters()
    {
        /* totest:
         * except == ''
         * except = array()
         * except = csv
         * various $_GET options
         * always_return_something true or false
         * method == querystring
         * method == hidden
         * method == catsatonthemat
         */
        $this->markTestIncomplete();
    }

    public function testUrlTo()
    {
        $this->markTestIncomplete();
        #test passing each section alone and getting an expected result. had to fix a bug coz I didn't include "id" in the sameness comparison
    }

    public function testRouteFromPath()
    {
        $this->markTestIncomplete();
    }
    public function testAsHiddens1()
    {
        $input = array('cat' => array(
                    'legs' => 4,
                    'tail' => 1,
                    'eyes' => 2
                )
            );
        $expected = '<input type="hidden" name="cat[legs]" value="4" /><input type="hidden" name="cat[tail]" value="1" /><input type="hidden" name="cat[eyes]" value="2" />';
        $result = as_hiddens($input);
        $this->assertEquals($expected, $result);
    }
    public function testAsHiddens2()
    {
        $input = array('cat' => array(
                'legs' => array(
                    'leg1' => 'brown',
                    'leg2' => 'white',
                    'leg3' => 'black',
                    'leg4' => 'white'
                ),
                'tail' => 1,
                'eyes' => 2
                )
            );
        $expected = '<input type="hidden" name="cat[legs][leg1]" value="brown" /><input type="hidden" name="cat[legs][leg2]" value="white" /><input type="hidden" name="cat[legs][leg3]" value="black" /><input type="hidden" name="cat[legs][leg4]" value="white" /><input type="hidden" name="cat[tail]" value="1" /><input type="hidden" name="cat[eyes]" value="2" />';
        $result = as_hiddens($input);
        $this->assertEquals($expected, $result);
    }
    public function testAsHiddens3()
    {
        $input = array(
                    'leg1' => 'brown',
                    'leg2' => 'white',
                    'leg3' => 'black',
                    'leg4' => 'white'
                );
        $expected = '<input type="hidden" name="leg1" value="brown" /><input type="hidden" name="leg2" value="white" /><input type="hidden" name="leg3" value="black" /><input type="hidden" name="leg4" value="white" />';
        $result = as_hiddens($input);
        $this->assertEquals($expected, $result);
    }

}

// Call functions_test::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'functions_test::main') {
    functions_test::main();
}
?>
