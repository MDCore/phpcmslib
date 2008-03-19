<?php
/* requires
 * we need to require everything we need here because allTests.php is not run when
 * executing phpunit <testClass>
 */
require_once '../functions.php';
require_once '../AR.php';
require_once '../action_controller.php';

class actionControllerTest extends PHPUnit_Framework_TestCase {
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

    public function test___construct() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
        /* controller name */

        /* layout */
    }
    public function test_handle_controller_filter() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function test_render() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    public function test_render_as_string() {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }



}
?>
