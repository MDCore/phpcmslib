<?php
/* requires
 * we need to require everything we need here because allTests.php is not run when
 * executing phpunit <testClass>
 */
require_once '../functions.php';
require_once '../string_helpers.php';
require_once '../filter.php';
require_once '../AR.php';
require_once '../action_controller.php';
require_once '../cm_controller.php';


/* DB TestCase */
require_once 'DB_TestCase.php';

class CMControllerTest extends DB_TestCase {
    /* construct, destruct, setup and teardown are handled by the
     * DB Testcase which recreates the test db 
     */

    public function test___construct()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
        /* face controller must be virtual */

        /* foreign keys */

        /* list_type */

        /* primary_model */

        /* list_title */

        /* records_per_page */

        /* add page title */

        /* edit page title */

        /* draw form buttons */

        /* show_delete */

        /* edit link title */

        /* model object */

        /* filter object */

        /* primary key field */

        /* schema table */

        /* sql query */

        /* allow filters */

        /* filters */

        /* has filters */

        /* foreign key title prefix */
    }

    public function test_cm_update()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    public function test_cm_update_core_simple()
    {

        $_POST = array('customer'
            => array(
                'name' => 'update_core_name'
            )
        );

        $cm = new customers_controller;
        $result = $cm->cm_update_core(1, $_POST);

        $customer = new customer;
        $customer->find(1);

        $this->assertEquals('update_core_name', $customer->name);
        $this->assertEquals('success', $result['result']);
    }
    public function test_cm_update_core_validation()
    {

        $_POST = array('customer'
            => array(
                'name' => '',
                'company_name' => 'update_core_company'
            )
        );

        $customer = new customer;
        $this->assertEquals('name', $customer->validates_presence_of[0]);

        $cm = new customers_controller;
        $result = $cm->cm_update_core(1, $_POST);

        $this->assertEquals('validation_failed', $result['result']);
    }

    public function test_cm_update_core_advanced()
    {
        $_POST = array(
            'product'
            => array(
                'name' => 'update_core_name'
            ),
            'category'
            => array(
                'name' => 'update_core_cat_name'
            )
        );

        $cm = new categories_controller;
        $results = $cm->cm_update_core(1, $_POST);

        $product = new product; $product->find(1);
        $cat = new category; $cat->find(1);

        $this->assertEquals('update_core_name', $product->name);
        $this->assertEquals('update_core_cat_name', $cat->name);
    }
    
}



class customers_controller extends cm_controller
{

}

class categories_controller extends cm_controller
{

}
?>
