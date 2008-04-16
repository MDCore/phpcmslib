<?php
/* requires
 * we need to require everything we need here because allTests.php is not run when
 * executing phpunit <testClass>
 */
require_once '../functions.php';
require_once '../AR.php';
require_once '../application.php';
require_once '../environment.php';

class functionsTest extends PHPUnit_Framework_TestCase {
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
        App::$env = new Environment;
        App::$default_face = 'site';
        App::$env->url = 'http://test.com';
        App::$route = array(
            'face' => 'cm',
            'controller' => 'products_controller',
            'action' => 'products_list',
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
        App::$env = null;
        App::$default_face = null;
        App::$route = null;
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
    /* I want this to work, but I understand why it doesn't. It doesn't because assuming where clauses 
     * need joining by AND is presumptive.
     * I could  change AR::sql_phrases['WHERE'] to ' AND ' but I think it might be counterproductive and 
     * prevent more advanced WHERE critera. That assertion needs testing though */
    public function testSQLImplode8() {
        $input = array(
            'WHERE' => array('1=1', "ub='abc'")
        );
        $expected =  "WHERE 1=1 AND ub='abc'";

        #$this->assertNotEquals($expected, SQL_implode($input));
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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

    public function test_url_to_basic() {
        $path = array(
            'face' => 'cm',
            'controller' => 'products',
            'action' => 'dogs',
        );
        $expected_path = 'http://test.com/cm/products/dogs';
        $result = url_to($path);
        $this->assertEquals($expected_path, $result, 'correct path is not being returned');

        $path = array(
            'face' => 'site',
            'controller' => 'products',
            'action' => 'dogs',
        );
        $expected_path = 'http://test.com/site/products/dogs';
        $result = url_to($path);
        $this->assertNotEquals($expected_path, $result, 'default face is still included');
        $expected_path = 'http://test.com/products/dogs';
        $result = url_to($path);
        $this->assertEquals($expected_path, $result);

        $path = array(
            'face' => 'site',
            'controller' => 'rupert',
            'action' => 'the_bear',
            'id' => 'rocks',
        );
        $expected_path = 'http://test.com/rupert/the_bear/rocks';
        $result = url_to($path);
        $this->assertEquals($expected_path, $result);

    }
    public function test_url_to_parameters() {
        /* default parameter options */
        $path = array(
            'face' => 'cm',
            'controller' => 'products',
            'action' => 'products_list',
        );
        $expected_path = 'http://test.com/cm/products/products_list';
        $result = url_to($path);
        $this->assertEquals($expected_path, $result, 'correct path is not being returned');

        $expected_path = '';
        $result = url_to($path);
        $this->assertNotEquals($expected_path, $result, "url is '' when route + target are the same even though include_base is true");

        /* test base url off */
        $expected_path = '';
        $result = url_to($path, false);
        $this->assertEquals($expected_path, $result, 'base url off is not returning emptystring');

        /* test explicit path on */
        $expected_path = 'http://test.com/cm/products/products_list';
        $result = url_to($path, true, true);
        $this->assertEquals($expected_path, $result, 'explicit path is not being returned correctly');
    }

    public function test_url_to_advanced() {
        App::$route = array(
            'face' => 'cm',
            'controller' => 'users_controller',
            'action' => 'cm_list',
            'id' => '',
        );
        $path = array(
            'face' => 'site'
        );

        /* tests default face */
        $expected_path = 'http://test.com/';
        $result = url_to($path);
        $this->assertEquals($expected_path, $result);
    }

    public function test_route_from_path() {
        App::$env = new Environment;
        App::$env->url = 'http://test.com';

        /* pass nothing */
        $expected_path = array(
            'face' => 'cm',
            'controller' => 'default_controller',
            'action' => '',
            'id' => '',
        );
        $path = '';
        $result = route_from_path($path);
        $this->assertEquals($result, $expected_path);

        /* pass string without slashes */
        $expected_path = array(
            'face' => 'cm',
            'controller' => 'cats_controller',
            'action' => '',
            'id' => '',
        );
        $path = 'cats';
        $result = route_from_path($path);
        $this->assertEquals($result, $expected_path);

        /* pass string with a slash */
        $expected_path = array(
            'face' => 'cm',
            'controller' => 'dogs_controller',
            'action' => 'cats',
            'id' => '',
        );
        $path = 'dogs/cats';
        $result = route_from_path($path);
        $this->assertEquals($result, $expected_path);

        /* pass string with 2 slashes */
        $expected_path = array(
            'face' => 'cm',
            'controller' => 'dogs_controller',
            'action' => 'cats',
            'id' => 'mice',
        );
        $path = 'dogs/cats/mice';
        $result = route_from_path($path);
        $this->assertEquals($result, $expected_path);

        /* pass string with different action */
        $expected_path = array(
            'face' => 'site',
            'controller' => 'default_controller',
            'action' => '',
            'id' => '',
        );
        $path = 'site';
        $result = route_from_path($path);
        $this->assertEquals($result, $expected_path);
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
?>
