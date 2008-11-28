<?
/* --- Usage ------------------------------------- */
/* beachhead PATH
 *
 * Note: The assumption is that you are running this on a dev server. So it updates
 * the dev environment and uses the deve environment to create the database. That
 * may change in the future
 */

/*
 * TODO
 * - Don't require the --project and --url arguments for a simple project creation.
 *   Rather check the project path for a leading / or ~ against the PWD and decide
 *   what to do from there.
 * - use MDB2 extended to create database
 * - account for remote repository failure or disconnection
 */

class tasks_beachhead
{
    /* defaults */
    public $root_url = '/';
    public $app_skeleton_repository = 'app_skeleton.git';
    public $app_skeleton_branch = 'master';
    public $submodules = array(
        'lib' => array('repository' => 'lib.git', 'branch' => 'version_3', 'path' => 'vendor/pedantic/lib')
        );

    public $strings = array(
        0 => 'No error found',
        100 => 'No path specified',
        101 => 'No project specified',
        102 => 'No repository URL specified',
        200 => 'Project directory %1 already exists',
        201 => 'Project directory %1 does not exist',
        300 => 'Cloning the application skeleton',
        301 => 'Switching to %1 branch',
        302 => 'Creating repository',
        303 => 'Committing application',
        304 => 'Setting up the submodules',
        305 => 'Configuring:',
        306 => '.htaccess',
        307 => 'config/application.php',
        308 => 'config/environments/development.php',
        309 => 'Creating the database',
        310 => 'Commiting auto-configuration',
        311 => 'Complete',
        /* commit messages */
        400 => 'Initial import\r\n\r\nImport by beachhead.',
        401 => 'Setting up submodules\r\n\r\nSetup by beachhead.',
        402 => 'Auto-configuration\r\n\r\nConfig by beachhead.',

    );
    public $error_no = 0;
    public $output_progress = false;

    public function run($arguments) {
        $path = $arguments[2];
        $repository_url = $arguments[3];

        /* get the project argument */
        if (isset($arguments['project'])) {
            $project = $arguments['project'];
        } else {
            $project = explode('/', str_replace('\\', '/', $path));
            $project = $project[sizeof($project)-1];
        }

        /* check the path and project */
        if (is_null($path) || $path == '') {
            $this->error(100); //'No path specified'
            return false;
        }
        if (is_null($project) || $project == '') {
            $this->error(101); //No project specified
            return false;
        }
        if (is_null($repository_url) || $repository_url == '') {
            $this->error(102); //No repository url specified
            return false;
        } else {
            $this->repository_url = $repository_url;
        }
        /* validate the repository url */
        if (substr($this->repository_url, -1, 1) != '/') {
            $this->repository_url .= '/';
        }
        /* check for additional arguments */
        if (isset($arguments['root_url'])) {
            $this->root_url = $arguments['root_url'];
        }
        return $this->create_application($path, $project);
    }
    public function __construct() {
    }

    private function create_application($path, $project) {

        $op = $this->output_progress;

        /* generate the filename / URL friendly project_name */
        $this->project_name = str_replace('.', '_', $project);
        $this->project_name = str_replace('/', '_', $project);
        $this->project_name = str_replace('\\', '_', $project);

        $this->project_path = $path;

        /* does this directory already exist ? fail! */
        if (file_exists($this->project_path)) {
            $this->error(200, array('%1' => $this->project_path));
            return false;
        }

        /* export the skeleton */
        if ($op) {
            echo $this->strings[300]."\r\n";
        }
        exec("git clone {$this->repository_url}{$this->app_skeleton_repository} {$this->project_path}", $output);

        if ($this->app_skeleton_branch != 'master') {
            if ($op) {
                echo sprintf($this->strings[301], $this->app_skeleton_branch)."\r\n";
            }
            exec("cd {$this->project_path} ; git checkout {$this->app_skeleton_branch} ; cd ../.. ", $output);
        }

        /* kill the git dir */
        exec("rm -rf {$this->project_path}/.git", $output);

        /* check that the project_path exists */
        if (!file_exists($this->project_path)) {
            $this->error(201, array('%1' => $this->project_path));
            return false;
        }

        /* init a new repository */
        if ($op) {
            echo $this->strings[302]."\r\n";
        }
        exec("cd {$this->project_path} ; git init", $output);

        /* commit */
        if ($op) {
            //echo $this->strings[303]."\r\n";
        }
        exec("cd {$this->project_path} ; git add . ; git commit -m '".$this->strings['400']."'", $output);

        /* set up the submodules in vendor */
        if ($op) {
            echo $this->strings[304]."\r\n";
        }
        foreach ($this->submodules as $submodule) {
            $repository = $submodule['repository'];
            $branch = $submodule['branch'];
            $path = $submodule['path'];
            if ($branch == '') {
                $branch = 'master';
            }

            //echo("cd {$this->project_path} ; git submodule add -b $branch {$this->repository_url}$repository $path\r\n");
            exec("cd {$this->project_path} ; git submodule add {$this->repository_url}$repository $path", $output, $return_status);
            exec("cd {$this->project_path}/$path ; git checkout -b $branch origin/$branch", $output, $return_status);
        }

        /* submodule commit */
        exec("cd {$this->project_path} ; git commit -a -m '".$this->strings['402']."'", $output);
        if ($op) {
            echo $this->strings[305];
        }

        /* sort out the project url */
        $project_url = $this->root_url.'/'.$project;
        $project_url = str_replace('//', '/', $project_url);

        if ($project_url[0] == '/') {
            $project_url = substr($project_url, 1);
        }

        /* customize the new skeleton for this project */
        /*
        * .htaccess
        *    URL_TO_DEV_SITE
         */
        if ($op) {
            echo $this->strings[306].", ";
        }
        replace_keywords_in_file($this->project_path.'/.htaccess',
            array('URL_TO_DEV_SITE' => $project_url)
        );

        /*
         * config/application
        *   YOUR_APP_NAME
        *   PASSWORD_SALT
        */
        if ($op) {
            echo $this->strings[307].", ";
        }
        replace_keywords_in_file($this->project_path.'/config/application.php',
            array(
                'YOUR_APP_NAME' => $this->project_name,
                'RANDOM_PASSWORD_SALT' => random_string(32, 'abcdefghijklmnopqrstuvwxyz!@#$%^&*+-=')
            )
        );

        /* config/environments/development.php
        *   DB_NAME
        *   SITE
        */
        if ($op) {
            echo $this->strings[308]."\r\n";
        }
        replace_keywords_in_file($this->project_path.'/config/environments/development.php',
            array(
                'DB_NAME' => $this->project_name,
                'DEV_PATH' => $this->project_path,
                'DEV_URL' => $project_url
            )
        );

        /* give everyone writes to change the schema def. Well, at least www user */
        chmod($this->project_path.'/config/cache/schema_definition.php', 0777);


        exec("cd {$this->project_path} ; git commit -a -m '".$this->strings['401']."'", $output);
        if ($op) {
            //echo "\r\n".$this->strings[310]."\r\n";
        }

        /* creating the database */
        if ($op) {
            echo $this->strings[309]."\r\n";
        }

        /* this checks that the class has not already been declared. This was
         * happening in a phpunit --repeat n scenario.
         */
        if (!class_exists('development')) {
            require($this->project_path.'/config/environments/development.php');
        }

        $env = new development; $dsn = $env->dsn;
        /*  $dsn's database does not exist yet (probably) so we need to blank it out
         *  here so that we can still connect
         */
        $dsn['database'] = null;
        $database_created = $this->create_database($dsn, $this->project_name);
        /* it doesn't matter if the database already exists as long as we don't
         * drop it
         */

        /* run the first migration if create_database returned true */
        if ($database_created) {
            //todo run the first migration

        }

        if ($op) {
            echo "\r\n".$this->strings[311];
        }
        return true;
    }

    public function create_database($dsn, $db_name) {
        $db =& MDB2::Connect($dsn); App::error_check($db);
        $result = $db->query('CREATE DATABASE '.$db_name);
        $result_code = AR::error_check($result, false);
        if (!is_null($result_code)) {
            return false;
        }
        return true;
    }

    public function error($error_no, $error_variables = null) {
        $this->error_no = $error_no;
        $this->error_variables = $error_variables;

        $error = $this->strings[$this->error_no];
        if (isset($this->error_variables) && is_array($this->error_variables)) {
            foreach ($this->error_variables as $variable => $value) {
                $error = str_replace($variable, $value, $error);
            }
        }
        $this->error = $error;

        if ($this->output_progress) {
            echo $error;
        }

        return $error_no;
    }
    public function show_output($output) {
        foreach($output as $line) {
            echo $line."\r\n";
        }
    }
}

?>
