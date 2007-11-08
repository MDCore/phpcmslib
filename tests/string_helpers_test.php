<?php
// Call string_helpers_test::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) { define('PHPUnit_MAIN_METHOD', 'string_helpers_test::main');
}

require_once 'PHPUnit/Framework.php';

require_once '../string_helpers.php';

/**
 * Test class for functions.php.
 */
class string_helpers_test extends PHPUnit_Framework_TestCase {
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */

    public static function main() {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('string_helpers_test');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function __construct() {
    }
    public function __destruct() {
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


    /* string helpers tests are GO! */
    public function testsplit_on_word() {
        $range = array(30, 40);
        $sentence = "to be or not to be that is the question whether this nobler in the mind to suffer the slings and arrows of outrageous fortune...";
        $expected = "to be or not to be that is the question";
        $this->assertEquals($expected, split_on_word($sentence, $range));

        $range = array(0, 15);
        $sentence = "to be or not. To be that is the question whether this nobler in the mind to suffer the slings and arrows of outrageous fortune...";
        $expected = "to be or not.";
        $this->assertEquals($expected, split_on_word($sentence, $range));

        $range = array(0, 15);
        $sentence = "to be. or not To be that is the question whether this nobler in the mind to suffer the slings and arrows of outrageous fortune...";
        $expected = "to be. or not";
        $this->assertEquals($expected, split_on_word($sentence, $range));

        $range = array(30, 40);
        $sentence = "Mel ne quot' singulis instructior, vel facilisis interpretaris ea! Feugait moderatius comprehensam an quo. Platonem ullamcorper sit cu. Ne vim aeterno electram efficiendi, in antiopam adolescens scriptorem has, quas aeque tantas in sea? Et antiopam peric";
        $expected = "Mel ne quot' singulis instructior, vel";
        $this->assertEquals($expected, split_on_word($sentence, $range));

        $range = array(30, 40);
        $sentence = "Private -- under contract BEIJ Trust (AS)";
        $expected = "Private -- under contract BEIJ Trust";
        $this->assertEquals($expected, split_on_word($sentence, $range));

        
    }

}

// Call string_helpers_test::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'string_helpers_test::main') {
    string_helpers_test::main();
}
?>
