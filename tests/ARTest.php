<?php
// Call ARTest::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) { define('PHPUnit_MAIN_METHOD', 'ARTest::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'MDB2.php';

require_once '../AR.php';
require_once '../functions.php';

#test models
require_once 'models.php';

/**
 * Test class for AR.
 * Generated by PHPUnit on 2007-07-08 at 06:41:35.
 */
class ARTest extends PHPUnit_Framework_TestCase {
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */

    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('ARTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function __construct()
    {
        $dsn = array(
            'phptype' => 'mysql',
            'username' => 'root',
            'password' => '',
            'hostspec' => 'localhost'
        );
        $this->db =& MDB2::Connect($dsn);
        App::error_check($this->db);

        $this->db->query('DROP DATABASE IF EXISTS ARTest');
        $this->db->query('CREATE DATABASE ARTest');
        App::error_check($this->db);
        
    }
    public function __destruct()
    {
        $this->db->query('DROP DATABASE IF EXISTS ARTest');
        App::error_check($this->db);
        unset($this->db);
    }
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() 
    {

        #setup the tables
            foreach (App::$schema_sql as $class_name => $query)
            {
                $this->db->query($query['create']);
                App::error_check($this->db);
                $this->db->query($query['insert']);
                App::error_check($this->db);
            }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        #delete all the tables
        foreach (App::$schema_sql as $class_name => $query)
        {
            $model_object = new $class_name;
            $drop_sql = 'DROP TABLE '.$model_object->schema_table;
            $this->db->query($drop_sql);
            App::error_check($this->db);
        }
    }

    /*
     * test stuff that happens on construction
     */

    public function test_dirty_property()
    {
        $collection = array('name' => 'test_init_with_collection_sets_dirty');
        $customer = new customer($collection);
        $this->assertTrue($customer->dirty, 'dirty not being set to true');
        $customer->find(1);
        $this->assertFalse($customer->dirty, 'dirty not false after find');
    }

    public function testConnect_to_db() {
        #not very good testing
        $customer = new customer;
        $this->assertNotNull($customer->db, 'connection to database has not been established');
    }

    public function testSetup_attributes() {
        $customer = new customer;
        $this->assertAttributeNotEquals(null,'schema_definition', $customer, 'schema_definition not set');
        $this->assertObjectHasAttribute('id', $customer, 'schema_definition not set');
        $this->assertAttributeEquals(null,'id', $customer, 'schema_definition not set');

        $test = new model_without_schema_def;
        $this->assertFalse($test->setup_attributes(), 'setup_attributes must return false without schema definition');
        $this->assertAttributeEquals(null,'schema_definition', $test, 'schema_definition exists');
    }

    /**
     * test __call().
     */
    public function test_bad_method_call()
    {
        $customer = new customer;
        try
        {
           $foo = $customer->ASDASDASD();
        }
        catch(Exception $e)
        {
            return;
        }

        $this->fail('An exception was not raised');
    }

    public function test_single_finder()
    {
        $customer = new customer;
        $this->assertTrue($customer->find_by_id(1));
        $this->assertEquals('cust 1',$customer->name);
        
        $test = new customer;
        $this->assertTrue($test->find_by_name('cust 1'));
        $this->assertEquals(1, $test->id);

    }

    public function test_find_all()
    {
        $customer = new customer;
        $this->assertTrue($customer->find('all'));
        $this->assertEquals(1, $customer->count);
    }

    public function test_single_finder_bad_data()
    {
        $customer = new customer;
        $this->assertFalse($customer->find_by_id(999));
        $this->assertEquals(0, $customer->count);
    }

    public function test_single_finder_with_attributes()
    {
        $customer = new customer;
        $this->assertTrue($customer->find('all', array('ORDER BY' => 'id DESC')));
        $this->assertEquals('SELECT * FROM customers WHERE 1=1 ORDER BY id DESC', $customer->last_sql_query);
    }

    public function test_single_finder_with_attributes2()
    {
        $customer = new customer;
        $this->assertTrue($customer->find_by_name('cust 1', array('ORDER BY' => 'id DESC')));
        $this->assertEquals("SELECT * FROM customers WHERE (name = 'cust 1') ORDER BY id DESC", $customer->last_sql_query);
    }
    public function test_single_finder_with_attributes3()
    {
        $customer = new customer;
        $this->assertTrue($customer->find_by_name('cust 1', array('WHERE' => 'AND name != \'cats\'')));
        $this->assertEquals("SELECT * FROM customers WHERE (name = 'cust 1') AND name != 'cats'", $customer->last_sql_query);
    }
    public function test_single_finder_with_attributes4()
    {
        $customer = new customer;
        $this->assertTrue($customer->find_by_name('cust 1', array('WHERE' => array("AND name != 'cats'", "AND name != 'lol'"))));
        $this->assertEquals("SELECT * FROM customers WHERE (name = 'cust 1') AND name != 'cats' AND name != 'lol'", $customer->last_sql_query);
    }

    public function test_multiple_finder()
    {
        $customer = new customer;
        $this->assertTrue($customer->find_by_id_and_name(1, 'cust 1'));
        $this->assertTrue($customer->id == 1);
        $this->assertTrue($customer->name == 'cust 1');
    }

    public function test_multiple_finder2()
    {
        $customer = new customer;
        $this->assertTrue($customer->find_by_id_and_name(1, 'cust 1', array('ORDER BY' => array('id DESC'))));
        $this->assertTrue($customer->id == 1);
        $this->assertTrue($customer->name == 'cust 1');
    }

    public function test_multiple_finder_bad_data()
    {
        $customer = new customer;
        $this->assertFalse($customer->find_by_id_and_name(999, 'bob'));
        $this->assertEquals(0, $customer->count);
    }

    /**
     * test __get().
     */
    public function test_bad_attributes()
    {
        $customer = new customer;
        try
        {
           $foo = $customer->ASDASDASD;
        }
        catch(Exception $e)
        {
            return;
        }
        $this->fail('An exception was not raised');
    }

    public function test_has_one()
    {
        $product = new product;
        $this->assertFalse($product->category, 'relationship is being found despite there being no records');

        $product->find(1);
        $this->assertEquals('category', get_class($product->category));
    }
    public function test_find_belongs_to()
    {
        $category = new category;
        $category->find(1);
        $this->assertEquals('product', get_class($category->products));
        $this->assertEquals(1, $category->products->count);
    }
    public function test_find_has_many_through()
    {
        $user = new user;
        $user->find(1);
        $this->assertEquals('find', get_class($user->finds));
        $this->assertEquals(1, $user->finds->count);
    }
    public function test_find_HMT_link_table()
    {
        $user = new user;
        $user->find(1);
        $this->assertEquals('user_find', get_class($user->user_finds));
        $this->assertEquals(1, $user->user_finds->count);
    }

    /* 
     * save tests
     */
    public function test_save_from_collection() {
        $collection = array('name' => 'new name');
        $customer = new customer($collection);
        $this->assertEquals(2, $customer->save());

    }
    public function test_save_cannot_set_id()
    {
        /*
        $collection = array('id' => 80, 'name' => 'new name');
        $customer = new customer($collection);
        $this->assertNotEquals(80, $customer->save());

        $test = new customer; $test->find(3);
        $this->assertEquals('new name', $test->name);
         */
        $this->markTestIncomplete();
    }

    public function test_save_with_changelog_adds_to_changelog()
    {
        $this->markTestIncomplete();
    }

    public function testSave_multiple() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /*
     * update tests
     */
    public function test_update_checks_for_target_record_first()
    {
        $customer = new customer;
        $customer->find(999);
        $this->assertFalse($customer->update());
    }

    public function test_update_saves_to_database() {
        
        $customer = new customer;
        $customer->find(1);
        $customer->name = 'new customer';
        $this->assertEquals(1, $customer->update(), 'update was not successful');

        $test_model = new customer;
        $test_model->find(1);
        $this->assertEquals('new customer', $test_model->name, "customer name not 'new customer'");

        #cleanup
            $customer = null;
            $test_model = null;
    }

    public function test_should_not_update_if_not_linked_to_a_record()
    {
        $customer = new customer;
        $this->assertFalse($customer->update(), 'Successful update on unlinked record');
    }

    /*
     * delete tests
    */
    public function test_delete_without_criteria()
    {
        $customer = new customer;
        $customer->find(1);
        $this->AssertTrue($customer->delete());
        $customer = null;

        $test = new customer;
        $this->assertFalse($test->find(1));
    }

    public function test_delete_with_criteria()
    {
        $customer = new customer;$customer->find(1);
        $this->AssertTrue($customer->delete('WHERE id=1'));
    }
    public function test_delete_with_bad_criteria()
    {
        /*
        $customer = new customer;$customer->find(1);
        $this->AssertFalse($customer->delete('WHERE id=999'), 'This method must return false if no records were affected');
         */
        $this->markTestIncomplete();
    }

    public function test_delete_on_empty()
    {
        $customer = new customer;
        $this->AssertFalse($customer->delete());
    }

    public function test_delete_with_changelog_marks_as_deleted() {
        $this->markTestIncomplete();
    }

    /*
     * other changelog tests
     */

    public function test_changelog_table_with_action_sets_actions()
    {
        $this->markTestIncomplete();
        #todo all CUD actions
    }

    public function test_changelog_without_action()
    {
        $this->markTestIncomplete();
    }

    /* 
     * misc
     */
    public function test_AR_disallows_changing_id()
    {
        /*
        $customer = new customer;$customer->find(1);
        try
        {
            $customer->id = 2;
        }
        catch(Exception $e)
        {
            return;
        }

        $this->fail('An exception was not raised');
*/
        $this->markTestIncomplete();
    }

    public function testWrite_value_changes() {
        $this->markTestIncomplete();
    }

    /**
     * @todo Implement testUpdate_attributes().
     */
    public function testUpdate_attributes() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testClear_attributes().
     */
    public function testClear_attributes() {
        $customer = new customer;
        $customer->find(1);
        $customer->clear_attributes();
        $this->assertNotEquals(1, $customer->id); 
        $customer_table = null;

        $collection = array('name' => 'new name');
        $c2 = new customer($collection);
        $c2->clear_attributes();
        $this->assertFalse($c2->dirty, 'this method must set dirty to false');
        $this->assertNotEquals('new name', $customer->name);
        $c2 = null;
    }

    /**
     * @todo Implement testAs_array().
     */
    public function testAs_array() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testAs_select_options().
     */
    public function testAs_select_options() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testHas_one().
     */
    public function testHas_one() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testThrough_model().
     */
    public function testThrough_model() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testHas_many_through().
     */
    public function testHas_many_through() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testRequirements().
     */
    public function testRequirements() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testIs_valid().
     */
    public function testIs_valid() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCriteria_to_sql().
     */
    public function testCriteria_to_sql() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testDisplay_name()
    {
        $customer = new customer;
        $this->assertFalse($customer->display_name());

        $customer->find(1);
        $this->assertEquals('company 1', $customer->display_name());

        $user = new user; $user->find(1);
        $this->assertEquals('Jim', $user->display_name());

        $user_find = new user_find; $user_find->find(1);
        $this->assertEquals(1, $user_find->display_name());
    }
}

// Call ARTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'ARTest::main') {
    ARTest::main();
}
?>
