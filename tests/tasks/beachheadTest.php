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
    public $project_dir = '/tmp/bob_client/bob_project';
    public $repository_url = 'file:///home/gavin/public_html/pedantic';
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
        if (file_exists($this->project_dir)) {
            system("rm -rf {$this->project_dir}");
        }
    }
    protected function tearDown() {
        if (file_exists($this->project_dir)) {
            system("rm -rf {$this->project_dir}");
        }
    }

    public function test_new_app() {
        $arguments = array(null, 'beachhead', $this->project_dir, $this->repository_url);

        $tb = new tasks_beachhead;
        $tb->submodules['lib']['repository'] = 'lib'; /* not pulling from a bare repo */
        $tb->app_skeleton_repository = 'app_skeleton'; /* not pulling from a bare repo */
        $result = $tb->run($arguments);

        $this->assertTrue($result, $tb->error);
        $this->assertTrue(file_exists($this->project_dir), 'Project directory does not exist');
        $this->assertTrue(file_exists($this->project_dir.'/vendor/pedantic/lib'), 'pedantic/lib does not exist');
        $this->assertTrue(file_exists($this->project_dir.'/vendor/pedantic/lib/AR.php'), 'pedantic/lib was not checkout out correctly');
    }
    public function test_new_app_dir_exists() {
        $project_dir = '/tmp/catsatonthemat';
        $arguments = array(null, 'beachhead', $project_dir, $this->repository_url);

        /* create the project_dir */
        exec("rm -rf $project_dir");
        exec("mkdir $project_dir");

        $tb = new tasks_beachhead;
        $tb->submodules['lib']['repository'] = 'lib'; /* not pulling from a bare repo */
        $tb->app_skeleton_repository = 'app_skeleton'; /* not pulling from a bare repo */
        $result = $tb->run($arguments);

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
