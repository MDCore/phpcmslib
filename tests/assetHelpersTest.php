<?php
/* requires
 * we need to require everything we need here because allTests.php is not run when
 * executing phpunit <testClass>
 */
require_once 'asset_helpers.php';
require_once 'string_helpers.php';
require_once 'functions.php';
require_once 'application.php';

class assetHelpersTest extends PHPUnit_Framework_TestCase {

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
?>
