<?php
/* todo
 * - create a table with stacks and stacks of data and modify certain tests to use that
 */                                                      

// Call ARTest::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) { define('PHPUnit_MAIN_METHOD', 'ARTest::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'MDB2.php';

require_once '../AR.php';
require_once '../functions.php';

#test models
require_once 'mocks/models.php';

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
            'username' => 'dev',
            'password' => 'dev',
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
                if (is_array($query['insert']))
                {
                    foreach ($query['insert'] as $statement)
                    {
                        $this->db->query($statement);
                        App::error_check($this->db);
                    }
                }
                else
                {
                    $this->db->query($query['insert']);
                }
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

    public function test_dirty_and_new_properties()
    {
        $user = new user;
        $this->assertFalse($user->dirty, 'a brand new record should start out clean');
        $this->assertTrue($user->new);


        $collection = array('name' => 'test_init_with_collection_sets_dirty');
        $customer = new customer($collection);
        $this->assertTrue($customer->dirty, 'dirty not being set to true');
        $this->assertTrue($user->new);
        $customer->find(1);
        $this->assertFalse($customer->dirty, 'dirty not false after find');
        $this->assertFalse($customer->new, 'a record that has been found should not be new anymore');
    }
    public function testConnect_to_db() {
        #not very good testing
        $customer = new customer;
        $this->assertNotNull($customer->db, 'connection to database has not been established');
    }

    public function testSetup_attributes() {
        $customer = new customer;
        $this->assertAttributeNotEquals(null,'schema_definition', $customer, 'schema_definition not set');

        $test = new model_without_schema_def;
        $this->assertFalse($test->setup_attributes(), 'setup_attributes must return false without schema definition');
        $this->assertAttributeEquals(null,'schema_definition', $test, 'schema_definition exists');
    }

/*
 * test __call()
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
    public function test_find_without_criteria_or_options()
    {
        $customer = new customer;
        try
        {
            $customer->find();
        }
        catch(Exception $e)
        {
            return;
        }

        $this->fail('An exception was not raised');
    }

    public function test_find_with_only_additional_options()
    { 
        $customer = new customer;
        $this->assertTrue($customer->find(null, 
            array('WHERE' => "name like '%cust%'")
        ));

        $this->assertEquals(1, $customer->count);
    }

    public function test_find_all()
    {
        $customer = new customer;
        $this->assertTrue($customer->find('all'));
        $this->assertEquals(1, $customer->count);
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

    public function test_special_finders()
    {
        $customer = new customer;
        $customer->find_most_recent_by_id(1);
        $this->assertEquals(1, $customer->id);

        $collection = array('name' => 'new name');
        $customer = new customer($collection);
        $customer->save();

        $ct = new customer;
        $ct->find_most_recent_by_id_and_name(2, 'new name');
        $this->assertEquals(2, $customer->id);
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

/*
 * test __isset()
 */
    public function test__isset()
    {
        $customer = new customer;
        $this->assertTrue(isset($customer->id));
    }
/*
 * test __get()
 */
    public function test_get_record()
    {
        $customer = new customer;
        $customer->find(1);
        $this->assertNotNull($customer->record, 'record property is not returning array of record values');
        $this->assertEquals(1, $customer->record['id']);
    }

    #bug: these additional criteria need to be upper case for SQL 
    public function test_get_additional_criteria_from_relationships()
    {
        #has many
            $category = new category;
            $category->find(1);
            $this->assertEquals(1, $category->count);
            $products = $category->products;
            $this->assertContains('ORDER BY products.name', $products->last_sql_query);
    }

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
    public function test_for_values_that_exists()
    {
        $customer = new customer;
        $this->assertTrue(isset($customer->id));
    }

    public function test_has_one()
    {
        $customer = new customer;
        $this->assertFalse($customer->car, 'relationship is being found despite there being no records');

        $customer->find(1);
        $this->assertEquals('car', get_class($customer->car));
    }
    public function test_has_many()
    {
        $category = new category;
        $category->find(1);
        $this->assertEquals('product', get_class($category->products));
        $this->assertEquals(2, $category->products->count);
    }
    public function test_has_many_through()
    {
        $user = new user;
        $user->find(1);
        $this->assertEquals('find', get_class($user->finds));
        $this->assertEquals(1, $user->finds->count);
    }
    public function test_HMT_link_table()
    {
        $user = new user;
        $user->find(1);
        $this->assertEquals('user_find', get_class($user->user_finds));
        $this->assertEquals(1, $user->user_finds->count);
    }
    public function test_belongs_to() {
        $product = new product;
        $this->assertEquals('category', $product->belongs_to);
        $product->find(1);
        $this->assertEquals('category', get_class($product->category));
        $this->assertEquals(1, $product->category->count);

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

/*
 * test __set()
 */
    public function test___set_modifies_values()
    {
        $customer = new customer;
        $customer->name = 'new_name';
        $this->assertEquals('new_name', $customer->name);
        $customer->model_name = 'testset';
        $this->assertEquals('testset', $customer->model_name);
    }
    public function test___set_sets_dirty()
    {
        $customer = new customer;
        $customer->name = 'new_name';
        $this->assertTrue($customer->dirty);
    }

    /* validation tests */
    public function test_is_valid()
    {
        /* test:
         *      validates_presence_of
         *      custom validate method
         */
        $this->markTestIncomplete();
    }
    /* 
     * save tests
     */
    public function test_save_with_direct_value_updates()
    {
        $category = new category;
        $category->name = 'a new category';
        $this->assertTrue($category->is_valid());
        $result = $category->save();
        $this->assertEquals(2, $result, 'this save should be successful since this is a valid record: '.$category->validation_errors);
    }

    public function test_save_from_collection() {

        $customer = new customer; $customer->find('all');
        $this->assertEquals(1, $customer->count);

        $collection = array('name' => 'new name');
        $customer = new customer($collection);
        $this->assertEquals(2, $customer->save());

    }
    public function test_save_cannot_set_id()
    {
        $collection = array('id' => 80, 'name' => 'new name');
        $customer = new customer($collection);
        $customer_id = $customer->save();
        $this->assertNotEquals(80, $customer_id, 'customer id was set');
        $this->assertEquals(2, $customer_id);

        $test = new customer; $test->find(2);
        $this->assertEquals('new name', $test->name);
       
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
    public function test_delete_on_empty()
    {
        $customer = new customer;
        $this->AssertFalse($customer->delete());
    }

    /*
     * changelog tests
     */
    public function test_changelog_highest_revision()
    {
        $customer = new customer;
        $result = $customer->changelog_highest_revision(1);
        $this->assertEquals(1, $result[0], 'changelog_highest_revision not returning results');
    }
    public function test_changelog_relationship()
    {
        $customer = new customer; $customer->find(1);
        $this->assertEquals('customer_changelog', get_class($customer->changelog));

        $user = new user;
        try
        {
            $f = $user->changelog;
        }
        catch(Exception $e)
        {
            return;
        }
        $this->fail('An exception was not raised when trying to catch to find a changelog on a changelog-less model');
    }

    public function test_changelog_insert()
    {
        $customer =  new customer();
        $customer->find(1);
        $collection = array('name' => 'new name');
        $customer->update_attributes($collection);
        $customer->update();

        #indirect test
        $this->assertEquals(2, $customer->changelog->revision, 'indirect test failed');

        #direct test
        $cc = new customer_changelog;
        $cc->find_most_recent_by_customer_id(1);
        $this->assertEquals(2, $cc->revision, 'direct test failed');

    }
    public function test_changelog_delete()
    {
        $customer = new customer();
        $customer->find(1);
        $customer->delete();
        
        #indirect
        $cc = new customer_changelog;
        $cc->find_most_recent_by_customer_id(1);
        $this->assertEquals('delete', $cc->action, 'direct test failed');
        $this->assertEquals(2, $cc->revision, 'direct test failed');

        #direct
        try
        {
            $t = $customer->changelog;

        }
        catch(Exception $e)
        {
            return;
        }
        $this->fail('An exception was not raised when accessing the changelog on a non-existent customer');
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
     * testClear_attributes().
     */
    public function testClear_attributes()
    {
        $customer = new customer;
        $customer->find(1);
        $customer->clear_attributes();
        $this->assertNotEquals(1, $customer->id); 
        $customer = null;

        $collection = array('name' => 'new name');
        $c2 = new customer($collection);
        $c2->clear_attributes();
        $this->assertFalse($c2->dirty, 'this method must set dirty to false');
        $this->assertNotEquals('new name', $customer->name);
        $c2 = null;
    }

    public function test_as_collection()
    {
        $customer = new customer;
        $customer->find('all');
        $expected = array(1 => array('company_name' =>'company 1'));
        $this->assertEquals($expected, $customer->as_collection());

        $expected = array(1 => array('name' => 'cust 1'));
        $this->assertEquals($expected, $customer->as_collection('name'));

        $expected = array(1 => array('name' => 'cust 1', 'company_name' => 'company 1'));
        $this->assertEquals($expected, $customer->as_collection(array('name', 'company_name')));
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

    public function testCriteria_to_sql_single_id()
    {
        $customer = new customer;
        $this->assertEquals('customers.id=1', $customer->criteria_to_sql(1));
    }
    public function testCriteria_all()
    {
        $customer = new customer;
        $this->assertEquals('1=1', $customer->criteria_to_sql('all'));
    }
    public function testcriteria_to_sql_array_of_ids()
    {
        $customer = new customer;
        #array of id's
            $this->assertEquals('customers.id IN (1,2,4)', $customer->criteria_to_sql(array(1, 2, 4)));
        #empty array
            $this->assertEquals('1=2', $customer->criteria_to_sql(array()));
    }
    public function testcriteria_to_sql_pure_sql()
    {
        $customer = new customer;
        $this->assertEquals('WHERE t=f', 'WHERE t=f');
    }

    function testSum()
    {
        $customer = new customer;
        $this->assertFalse($customer->sum(), 'Model without sum_field is not returning false');

        $product = new product;
        $this->assertEquals(0, $product->sum(), 'Object with no records not returning 0');

        $product->find('all');
        $this->assertEquals(600, $product->sum());
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
