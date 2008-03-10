<?
/* requires
 * we need to require everything we need here because allTests.php is not run when
 * executing phpunit <testClass>
 */
define('RUNNING_UNIT_TESTS', true);
require_once 'MDB2.php';
require_once '../functions.php';
require_once '../application.php';
require_once '../environment.php';
require_once '../AR.php';
require_once '../tasks/beachhead.php';

class tasks_beachheadTest extends PHPUnit_Framework_TestCase {
    public $root_path = '/tmp';
    public $client = 'bob_client';
    public $project = 'bob_project';
    public function __construct() {
        $this->dsn = array(
            'phptype' => 'mysql',
            'username' => 'dev',
            'password' => 'dev',
            'hostspec' => 'localhost'
        );
    }
    public function __destruct() {
    }

    protected function setUp() {
        if (file_exists($this->root_path.'/'.$this->client)) {
            //system("rm -rf {$this->root_path}/$this->client");
        }
    }
    protected function tearDown() {
        if (file_exists($this->root_path.'/'.$this->client)) {
            //system("rm -rf {$this->root_path}/$this->client");
        }
    }

    public function test_create_application() {

        $tb = new tasks_beachhead;
        /* modify the public_html folder to use tmp */
        $tb->public_html_path = $this->root_path.'/';

        $project_dir = $this->root_path.'/'.$this->client.'/'.$this->project;
        $result = $tb->create_application($this->client, $this->project);

        $this->assertTrue($result, $tb->error());
        $this->assertTrue(file_exists($project_dir), 'Project directory does not exist');
        $this->assertTrue(file_exists($project_dir.'/vendor/pedantic/lib'), 'pedantic/lib does not exist');
        $this->assertTrue(file_exists($project_dir.'/vendor/pedantic/lib/AR.php'), 'pedantic/lib was not checkout out correctly');
    }
    public function test_create_application_dir_exists() {
        $new_client = "catsatonthemat";
        $new_project = "catproject";
        exec("rm -rf /tmp/catsatonthemat");
        exec("mkdir /tmp/catsatonthemat");
        exec("mkdir /tmp/catsatonthemat/catproject");

        $tb = new tasks_beachhead;
        /* modify the public_html folder to use tmp */
        $tb->public_html_path = $this->root_path.'/';

        $project_dir = $this->root_path.'/'.$new_client.'/'.$new_project;
        $result = $tb->create_application($new_client, $new_project);
        $this->assertFalse($result, 'project was created despite existing project directory');
    }

    public function test_create_database() {
        $db =& MDB2::Connect($this->dsn); App::error_check($db);
        $test_db_name = 'tasks_beachhead_test';

        /* drop the test database first; basically a custom setUp() */
        $sql = "DROP DATABASE IF EXISTS tasks_beachhead_test";
        $result = $db->query($sql); App::error_check($result);

        $tb = new tasks_beachhead;
        $result = $tb->create_database($this->dsn, $test_db_name);
        $this->assertTrue($result);

        /* test that it exists now */
        $sql = "USE $test_db_name";
        $result = $db->query($sql);
        $result_code = App::error_check($result, false);
        $this->assertNull($result_code, 'The database does not exist');

        /* drop the test database; basically a custom tearDown() */
        $sql = "DROP DATABASE IF EXISTS tasks_beachhead_test";
        $result = $db->query($sql); App::error_check($result);
    }
    
}
