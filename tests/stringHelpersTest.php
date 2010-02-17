<?php
/* requires
 * we need to require everything we need here because allTests.php is not run when
 * executing phpunit <testClass>
 */
require_once 'string_helpers.php';

class stringHelpersTest extends PHPUnit_Framework_TestCase {
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

    public function test_to_sentence() {
      $input = array('cats', 'dogs');
      $expected = 'cats and dogs';
      $this->assertEquals($expected, to_sentence($input));

      $input = array('a', 'b', 'c');
      $expected = 'a, b and c';
      $this->assertEquals($expected, to_sentence($input));

      $input = array('cats', 'dogs', 'mice', 'chicken');
      $expected = 'cats, dogs, mice and chicken';
      $this->assertEquals($expected, to_sentence($input));
    }
    public function test_proper_nounize() {
      $input = '';
      $expected = '';
      $this->assertEquals($expected, proper_nounize($input));

      $input = 'to be or not to be';
      $expected = 'To Be Or Not To Be';
      $this->assertEquals($expected, proper_nounize($input));

      $input = 'To BE or not to BE';
      $expected = 'To BE Or Not To BE';
      $this->assertEquals($expected, proper_nounize($input));
    }
    public function test_foreign_keyize(){
      $input = 'cats';
      $expected = 'cats_id';
      $this->assertEquals($expected, foreign_keyize($input));
    }
    public function humanize() {
      $input = 'cat sat on the mat';
      $expected = 'Cat sat on the mat';
      $this->assertEquals($expected, humanize($input));
    }
    public function test_tableize() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }
    public function test_sungularize() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }
    public function test_pluralize() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }
    public function test_proper_case() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }
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
    public function test_value_else_na() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }
    public function test_value_else_none() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );

    }
    public function test_sanitize_text() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );

    }
    public function test_h() {
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

}
?>
