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
        #setup the customer_table
            $this->db->query("
            CREATE TABLE ARTest.customers (
              `id` int(11) NOT NULL auto_increment,
              `created_on` datetime NOT NULL,
              `updated_on` datetime NOT NULL,
              `name` varchar(255) NOT NULL,
              `company_name` varchar(255) NOT NULL,
              `address` text NOT NULL,
              `active` char(1) NOT NULL default 'Y',
              PRIMARY KEY  (`id`)
              ) ENGINE=MyISAM DEFAULT CHARSET=latin1;"
            );
        App::error_check($this->db);

    }
    public function __destruct()
    {
        $this->db->query('DROP DATABASE IF EXISTS ARTest');
        unset($this->db);
    }
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {

        #echo "\r\nsetup\r\n"; 
        #customer fixtures
            $sql = "INSERT INTO ARTest.customers (id, name, company_name, address, active, created_on, updated_on) VALUES (1, 'cust 1', 'company 1', 'address 1', 'Y', now(), now())";
            $this->db->query($sql);
            #print_r($sql);
            #App::error_check($this->db);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
        #echo "\r\nteardown\r\n"; 
        $this->db->query('DELETE FROM ARTest.customers');
        #App::error_check($this->db);
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
        $this->assertTrue($customer->name = 'cust 1');
        
        $test = new customer;
        $this->assertTrue($test->find_by_name('cust 1'));
        $this->assertTrue($test->id = 1);
    }

    public function test_single_finder_bad_data()
    {
        $customer = new customer;
        $this->assertFalse($customer->find_by_id(999));
        $this->assertEquals(0, $customer->count);
    }

    public function test_multiple_finder()
    {
        $customer = new customer;
        $this->assertTrue($customer->find_by_id_and_name(1, 'cust 1'));
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

    public function test_follow_relationships()
    {
        $this->markTestIncomplete();
    }

    /**
     * @todo Implement testCreate().
     */
    public function testCreate()
    {
        #see testClearAttributes
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
        $collection = array('id' => 80, 'name' => 'new name');
        $customer = new customer($collection);
        $this->assertNotEquals(80, $customer->save());

        $test = new customer; $test->find(3);
        $this->assertEquals('new name', $test->name);
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
        $customer = new customer;$customer->find(1);
        $this->AssertFalse($customer->delete('WHERE id=999'), 'This method must return false if no records were affected');
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

    /**
     * @todo Implement testDisplay_name().
     */
    public function testDisplay_name() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}

// Call ARTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'ARTest::main') {
    ARTest::main();
}
?>
