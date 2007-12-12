<?php
// Call functions_test::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) { define('PHPUnit_MAIN_METHOD', 'functions_test::main');
}

require_once 'PHPUnit/Framework.php';
 
class asset_helpers_test extends PHPUnit_Framework_TestCase {

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
        App::$face = 'site';
        App::$env = new Environment;
        
        App::$env->root = '/websites/tests';
        App::$env->url = 'http://webserver/tests';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    }

    public function testAssetPath1()
    {
        $asset_name = 'foo.jpg';
        $asset_type = 'image';
        $face = null;

        $expected = App::$env->url.'/site/assets/images/foo.jpg';
        $result = asset_path($asset_type, $asset_name);
        $this->assertEquals($expected, $result);

    }
    public function testAssetPath2()
    {
        $asset_name = 'foo.css';
        $asset_type = 'stylesheet';
        $face = 'cm';

        $expected = App::$env->url.'/cm/assets/stylesheets/foo.css';
        $result = asset_path($asset_type, $asset_name, $face);
        $this->assertEquals($expected, $result);
    }
    public function testAssetPathBadAssetType()
    {
        $asset_name = 'foo.css';
        $asset_type = 'badasset';
        $face = 'cm';

        try
        {
            $result = asset_path($asset_type, $asset_name, $face);
        }
        catch(Exception $e)
        {
            return;
        }
        $this->fail('Invalid asset type not caught');
    }
    public function testAssetPathBadAssetName()
    {
        $asset_name = 'foo/../../taste';
        $asset_type = 'stylesheet';
        $face = 'cm';

        try
        {
            $result = asset_path($asset_type, $asset_name, $face);
        }
        catch(Exception $e)
        {
            return;
        }
        $this->fail('Invalid asset type not caught');
    }
    public function testAssetPathBadFace()
    {
        $asset_name = 'foo.css';
        $asset_type = 'stylesheet';
        $face = 'cm/../config.php?=';

        try
        {
            $result = asset_path($asset_type, $asset_name, $face);
        }
        catch(Exception $e)
        {
            return;
        }
        $this->fail('Invalid asset type not caught');
    }
}

// Call functions_test::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'functions_test::main') {
    functions_test::main();
}
?>
