<?php
/* notes:
 * these tests need the table large_test_data
 */

/* todo
 * - modify more tests to use the big test data
 * - use proper mocking and stubbing, not my handmade... stuff
 * - remove hardcoded DSN information
 */

/* requires
 * we need to require everything we need here because allTests.php is not run when
 * executing phpunit ARTest
 */
require_once 'string_helpers.php';
require_once 'functions.php';
require_once 'AR.php';
require_once 'application.php';

require_once 'DB/NestedSet.php' ;

/* DB TestCase */
require_once 'DB_TestCase.php';

class ARTest extends DB_TestCase {
    public $delete_db = false;

    public function test_dirty_and_new_properties() {
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
    public function test_bad_method_call() {
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
    public function test_find_without_criteria_or_options() {
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
    public function test_find_with_only_additional_options() {
        $customer = new customer;
        $customer->find(null,
            array('WHERE' => "name like '%Mary Williams%'")
        );

        $this->assertEquals(2, $customer->count);
    }
    public function test_find_by_id() {
        $customer = new customer;
        $this->assertEquals(1, $customer->find_by_id(1)->count);
    }
    public function test_find_all() {
        $customer = new customer;
        $customer->find('all');
        $this->assertEquals(5000, $customer->count);
    }
    public function test_single_finder() {
        $customer = new customer;
        $customer->find_by_id(1);
        $this->assertEquals('Mary Williams',$customer->name);

        $test = new customer;
        $test->find_by_name('Mary Williams');
        $this->assertEquals(1, $test->id);
        $this->assertEquals(2,$test->count);

        $test = new customer;
        $test->find_by_name('Stanton Woodruff');
        $this->assertEquals(2559, $test->id);

    }

    public function test_single_finder_bad_data() {
        $customer = new customer;
        $customer->find_by_id(50000);
        $this->assertEquals(0, $customer->count);
    }
    public function test_single_finder_with_attributes() {
        $customer = new customer;
        $customer->find('all', array('ORDER BY' => 'id DESC'));
        $this->assertEquals('SELECT ARTest.customers.* FROM ARTest.customers WHERE 1=1 ORDER BY id DESC', $customer->last_sql_query);
    }
    public function test_single_finder_with_attributes2() {
        $customer = new customer;
        $customer->find_by_name('Mary Williams', array('ORDER BY' => 'id DESC'));
        $this->assertEquals("SELECT ARTest.customers.* FROM ARTest.customers WHERE (ARTest.customers.name = 'Mary Williams') ORDER BY id DESC", $customer->last_sql_query);
    }
    public function test_single_finder_with_attributes3() {
        $customer = new customer;
        $customer->find_by_name('cust 1', array('WHERE' => 'AND name != \'cats\''));
        $this->assertEquals("SELECT ARTest.customers.* FROM ARTest.customers WHERE (ARTest.customers.name = 'cust 1') AND name != 'cats'", $customer->last_sql_query);
    }
    public function test_single_finder_with_attributes4() {
        $customer = new customer;
        $customer->find_by_name('cust 1', array('WHERE' => array("AND name != 'cats'", "AND name != 'lol'")));
        $this->assertEquals("SELECT ARTest.customers.* FROM ARTest.customers WHERE (ARTest.customers.name = 'cust 1') AND name != 'cats' AND name != 'lol'", $customer->last_sql_query);
    }

    public function test_special_finders() {
        $customer = new customer;
        $customer->find_most_recent_by_id(1);
        $this->assertEquals(1, $customer->id);

        $collection = array('name' => 'new name');
        $customer = new customer($collection);
        $this->assertEquals(5001, $customer->save());

        $ct = new customer;
        $ct->find_most_recent_by_id_and_name(5001, 'new name');
        $this->assertNotEquals('MDB2_Error', get_class($customer->id));
        $this->assertEquals(5001, $customer->id);
        $this->assertEquals('new name', $customer->name);
    }

    public function test_finder_is_an_object() {
        $customer = new customer;
        $this->assertEquals(get_class($customer), get_class($customer->find_by_id_and_name(2559, 'Stanton Woodruff', array('ORDER BY' => array('id DESC')))));
    }
    public function test_multiple_finder() {
        $customer = new customer;
        $customer->find_by_id_and_name(2559, 'Stanton Woodruff');
        $this->assertTrue($customer->id == 2559);
        $this->assertTrue($customer->name == 'Stanton Woodruff');
        $this->assertTrue(1 == $customer->count);
    }
    public function test_multiple_finder2() {
        $customer = new customer;
        $customer->find_by_id_and_name(2559, 'Stanton Woodruff', array('ORDER BY' => array('id DESC')));
        $this->assertTrue($customer->id == 2559);
        $this->assertTrue($customer->name == 'Stanton Woodruff');
        $this->assertTrue(1 == $customer->count);
    }
    public function test_multiple_finder_bad_data() {
        $customer = new customer;
        $this->assertEquals(0, $customer->find_by_id_and_name(99999, 'bob')->count);
    }
    public function test_finder_all_with_criteria() {
        $customer = new customer;
        $expected = 'SELECT ARTest.customers.* FROM ARTest.customers WHERE 1=1 AND id=2559';
        $customer->find('all', array('WHERE' => ' AND id=2559'));
        $result = $customer->last_sql_query;
        $this->assertEquals($expected, $result);
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
    public function testHas_many_through_returns_correct_records() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet. There was previously a bug in __get's HMT sql query that should have been test covered"
        );
    }

    /**
     * @todo Implement testRequirements().
     */


/* test bulk customers add */
    public function test_stress_large_no_of_object_creations()
    {
        return false; #todo move to stress_tests
        for ($i = 0; $i < 5000; $i++)
        {
            $customer = new customer; $customer = null;
            if ($i % 1000 == 0 && $i != 0) { echo $i."\r\n"; }
        }
        $this->assertTrue(true);
    }

    public function test_stress_bulk_record_add_small() {

    #todo, move large tests into stress tests, make it a fat number
        return false;
        $sql = "SELECT * FROM large_test_data.customers ORDER BY RAND() LIMIT 100000";
        $AR = new db_conn;$AR = $AR->db->query($sql);
        while ($row = $AR->fetchRow())
        {
            $count++;
            $data = array(
                'name' => $row->name.' '.$row->surname,
                'company_name' => $row->email_address,
                'address' => $row->email_address,
            );

            $customer = new customer;
            $customer->update_attributes($data);
            $customer->save();
            if ($count % 100 == 0 && $count != 0) { echo $count."\r\n"; }
        }
        $customer = new customer; $customer->find('all');
        $this->assertEquals(1001, $customer->count);
    }

/*
 * test __set()
 */
    public function test___set_modifies_values() {
        $customer = new customer;
        $customer->name = 'new_name';
        $this->assertEquals('new_name', $customer->name);
        $customer->model_name = 'testset';
        $this->assertEquals('testset', $customer->model_name);
    }
    public function test___set_sets_dirty() {
        $customer = new customer;
        $customer->name = 'new_name';
        $this->assertTrue($customer->dirty);
    }

    /* validation tests */
    public function test_is_valid() {
        /* test:
         *      validates_presence_of
         *      custom validate method
         */
        $this->markTestIncomplete();
    }
    /*
     * save tests
     */
    public function test_save_with_direct_value_updates() {
        $category = new category;
        $category->name = 'a new category';
        $this->assertTrue($category->is_valid());
        $result = $category->save();
        $this->assertEquals(2, $result, 'this save should be successful since this is a valid record: '.$category->validation_errors);
    }

    public function test_save_from_collection() {

        $customer = new customer; $customer->find('all');
        $this->assertEquals(5000, $customer->count);

        $collection = array('name' => 'new name');
        $customer = new customer($collection);
        $this->assertEquals(5001, $customer->save());

    }
    public function test_save_cannot_set_id() {
        $collection = array('id' => 80, 'name' => 'new name');
        $customer = new customer($collection);
        $customer_id = $customer->save();
        $this->assertNotEquals(80, $customer_id, 'customer id was set');
        $this->assertEquals(5001, $customer_id);

        $test = new customer; $test->find(5001);
        $this->assertEquals('new name', $test->name);

    }

    public function testSave_multiple() {
        $input = array(
            'user_id' => 1,
            'find_id'  => Array(2, 3)
        );
        $user_find = new user_find;
        $result = $user_find->save_multiple($input);

        $user = new user;
        $user->find(1);
        $this->assertEquals(3, $user->finds->count);


    }

    public function testSave_keeps_slashes() {
        $collection = array('name' => "new name's");
        $customer = new customer($collection);
        $this->assertEquals(5001, $customer->save());
        $customer = new customer();
        $customer->find(5001);
        $this->assertEquals("new name's", $customer->name);

        $collection = array('name' => "new name's");
        $customer = new customer();
        $customer->update_attributes($collection);
        $this->assertEquals(5002, $customer->save());
        $customer = new customer();
        $customer->find(5002);
        $this->assertEquals("new name's", $customer->name);
    }

    public function testSave_keeps_user_id() {
    /* there was a bug in AR::save() where it would set the user_id in the collection
     * to $_SESSION[APP_NAME]['user_id'] if it existed
     */
        define('APP_NAME', 'ARTest');
        $_SESSION[APP_NAME]['user_id'] = 17;

        $user_find = new user_find(array(
            'find_id' => 99,
            'user_id' => 25
        ));
        $uf_id = $user_find->save();

        $test = new user_find;
        $test->find_by_id($uf_id);
        $this->assertEquals(25, $test->user_id, 'AR::save() is changing the value of user_id');
    }

    /*
     * update tests
     */
    public function test_update_checks_for_target_record_first()
    {
        $customer = new customer;
        $customer->find(99999);
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
    public function test_should_not_update_if_not_linked_to_a_record() {
        $customer = new customer;
        $this->assertFalse($customer->update(), 'Successful update on unlinked record');
    }

    /*
     * delete tests
    */
    public function test_delete_without_criteria() {
        $customer = new customer;
        $customer->find(1);
        $this->AssertTrue($customer->delete());
        $customer = null;

        $test = new customer;
        $this->assertEquals(0, $test->find(1)->count);
    }
    public function test_delete_with_criteria() {
        $customer = new customer;$customer->find(1);
        $this->AssertTrue($customer->delete('id=1'));
    }
    public function test_delete_on_empty() {
        $customer = new customer;
        $this->AssertFalse($customer->delete());
    }

    /*
     * changelog tests
     */
    public function test_changelog_highest_revision() {
        $customer = new customer;
        $result = $customer->changelog_highest_revision(1);
        $this->assertEquals(1, $result[0], 'changelog_highest_revision not returning results');
    }
    public function test_changelog_relationship() {
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

    public function test_changelog_insert() {
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
    public function test_changelog_delete() {
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
    public function test_AR_disallows_changing_id() {
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
    public function testClear_attributes() {
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

        /*
         * not a test per se... but this code generates an error when it shouldn't. not sure how to test for that ( pre nov 2007 )
         * 2007-12-02: not sure this is giving an error anymore
         *
         *
         *check that clear_attributes() returns $this
         * */
            $customer = new customer;
            $customer->schema_definition = null;
            $this->assertEquals(get_class($customer), get_class($customer->clear_attributes()));
    }

    public function test_as_collection() {
        $customer = new customer;
        $customer->find(1);

        #single, using display field
            $expected = array(1 => array('company_name' =>'Williams'));
            $result = $customer->as_collection();
            $this->assertEquals($expected, $result);

        #single, using custom field
            $expected = array(1 => array('name' => 'Mary Williams'));
            $this->assertEquals($expected, $customer->as_collection('name'));

        #single, using custom fields
            $expected = array(
                1 => array(
                    'name' => 'Mary Williams',
                    'company_name' => 'Williams'
                    )
                );
            $this->assertEquals($expected, $customer->as_collection(array('name', 'company_name')));
        #multiple, using display_field
            #todo

        #custom method #todo

        #make sure it doesn't do anything when empty
            try
            {
                $user = new user;
                $user->as_collection();
            }
            catch(Exception $e)
            {
                return;
            }
            $this->fail('An exception was not raised');
    }
    public function test_as_collection_with_display_field_function()
    {
        // There is a definite bug with doing this!

        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    public function testAs_array() {

        $customer = new customer;
        $expected = array(1 => 'Mary Williams');
        $this->assertEquals($expected, $customer->find(1)->as_array('name'));

        $expected = array(1 => 1);
        $customer->display_field = 'id';
        $this->assertEquals($expected, $customer->as_array());

    }
    public function testAs_array_empty_AR() {
        try {
            $customer = new customer;
            $customer->as_array();
        }
        catch(Exception $e)
        {
            return;
        }
        $this->fail('An exception was not raised');

        #$expected = array();
        #$this->assertEquals($expected, $customer->as_array());
    }

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
        $this->assertEquals('ARTest.customers.id=1', $customer->criteria_to_sql(1));
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
            $this->assertEquals('ARTest.customers.id IN (1,2,4)', $customer->criteria_to_sql(array(1, 2, 4)));
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

        /* test a custom sum field */
        $product->find('all');
        $this->assertEquals(75, $product->sum('secondary_cost'));
    }

    public function testDisplay_name()
    {
        $customer = new customer;
        $this->assertFalse($customer->display_name());

        $customer->find(1);
        $this->assertEquals('Williams', $customer->display_name());

        $user = new user; $user->find(1);
        $this->assertEquals('Jim', $user->display_name());

        $user_find = new user_find; $user_find->find(1);
        $this->assertEquals(1, $user_find->display_name());

        $customer->find(1); $customer->display_field = 'id';
        $this->assertEquals(1, $customer->display_name());

        // test for apostrophe's and stripslashes
        $customer = new customer;
        $customer->find(1); $customer->company_name = "O' Reilly"; $customer->save();
        $customer->find_by_id(1);
        $this->assertEquals("O' Reilly", $customer->display_name());
    }

    /* acts_as_nested_set tests */
    public function testnested_set_children_method()
    {
        $test = new customer;
        try
        {
            $test->children();
        }
        catch(exception $e)
        {
            return;
        }
        $this->fail('an exception was not raised');
    }
    public function testnested_set_children_method2()
    {
        $test = new tree_table;
        $this->assertEquals(null, $test->children());
    }
    public function testnested_set_basics()
    {
        $cat = new tree_table;
        $this->assertTrue($cat->acts_as_nested_set);

        $test = new tree_table;

        //father
        $cat = new tree_table(
            array('name' => 'Father', 'sum_test' => 65)
        );
        $cat->save();

        /* check that sum_test was saved */
        $this->AssertEquals(65, $test->find(1)->sum_test);
        $this->assertEquals(1, $test->find('all')->count);

        //son
        $cat = new tree_table;
        $cat->parent_id = 1;
        $cat->name = 'Son'; $cat->sum_test = 35;
        $cat->save();

        /* the father node has only one child, the son */
        $this->assertEquals(1, $test->find_by_id(1)->children()->count, 'the father node should have 1 child');

        //grandson
        $cat = new tree_table;
        $cat->parent_id = 2;
        $cat->name = 'Grandson'; $cat->sum_test = 10;
        $result = $cat->save();

        $this->assertEquals(3, $test->find('all')->count);

        /* the son node has only one child, the grandson */
        $this->assertEquals(1, $test->find_by_id(2)->children()->count, 'the son node should only have one child, the grandson');

        /* the father node has 3 nodes in its subbranch */
        $this->assertEquals(2, $test->find_by_id(1)->sub_branch()->count, 'the father node should have 2 nodes in its sub_branch');

        $this->assertEquals(45, $test->find_by_id(1)->sub_branch()->sum('sum_test'));

        //great-grandson
        $cat = new tree_table;
        $cat->parent_id = 3;
        $cat->name = 'Great-grandson'; $cat->sum_test = 5;
        $result = $cat->save();

        /* the father node now has 3 nodes in its subbranch */
        $this->assertEquals(3, $test->find_by_id(1)->sub_branch()->count, 'The father node should have 3 nodes in its subbranch.');

        /* the son node has 2 nodes in its branch */
        $this->assertEquals(2, $test->find_by_id(2)->sub_branch()->count, 'The son node should have 2 nodes in its subbranch.');

        $this->assertEquals(50, $test->find_by_id(1)->sub_branch()->sum('sum_test'));
    }


    public function testnested_set_multiple_root_nodes()
    {
        $cat = new tree_table(array('name' => 'grandfather', 'sum_test' => 150));
        $this->assertEquals(1, $cat->save());
        $cat = new tree_table(array('name' => 'father', 'sum_test' => 125));
        $this->assertEquals(2, $cat->save());
        $cat = new tree_table(array('name' => 'son', 'sum_test' => 100));
        $this->assertEquals(3, $cat->save());
        $cat = new tree_table(array('name' => 'grandson', 'sum_test' => 75));
        $this->assertEquals(4, $cat->save());
        $cat = new tree_table(array('name' => 'great-grandson', 'sum_test' => 75));
        $this->assertEquals(5, $cat->save());

        /* check that all the records are there */
        $test = new tree_table;
        $this->assertEquals(5, $test->find('all')->count);
    }
    public function testnested_set_bigger_tree()
    {
        /* the tree */
        $cat = new tree_table(array('name' => 'father', 'sum_test' => 125)); $cat->save();
        $cat = new tree_table(array('ns_parent_id' => 1, 'name' => 'eldest son', 'sum_test' => 100)); $cat->save();
        $cat = new tree_table(array('ns_parent_id' => 1, 'name' => 'middle son', 'sum_test' => 100)); $cat->save();
        $cat = new tree_table(array('ns_parent_id' => 1, 'name' => 'youngest son', 'sum_test' => 100)); $cat->save();

        $cat = new tree_table(array('ns_parent_id' => 3, 'name' => 'daughter of middle son', 'sum_test' => 100)); $cat->save();
        $cat = new tree_table(array('ns_parent_id' => 3, 'name' => 'son of middle son', 'sum_test' => 100)); $cat->save();

        $cat = new tree_table(array('ns_parent_id' => 2, 'name' => 'daughter of eldest son', 'sum_test' => 100)); $cat->save();

        $cat = new tree_table(array('name' => 'brother of father', 'sum_test' => 125)); $cat->save();
        $cat = new tree_table(array('name' => 'sister of father', 'sum_test' => 125)); $cat->save();

        $cat = new tree_table(array('ns_parent_id' => 5, 'name' => 'grandaughter of father, daughter of daughter of middle son', 'sum_test' => 125)); $cat->save();

        /* the tests */
        $test = new tree_table;
        $this->assertEquals(1, $test->find_by_id(1)->count, 'Record #2 not found');

        $this->assertEquals(7, $test->find_by_id(1)->sub_branch()->count, 'The father has 7 people in his sub_tree');
        $this->assertEquals(3, $test->find_by_id(3)->sub_branch()->count, 'The middle son has 3 people in his sub_tree');

        $this->assertEquals(5, $test->find_by_id(10)->ns_parent_id, 'parent_id should be 5');
    }

}
?>
