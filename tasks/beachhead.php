<?
/* --- Usage ------------------------------------- */
/* beachhead CLIENT_NAME PROJECT_NAME
 *
 * Note: The assumption is that you are running this on a dev server. So it updates
 * the dev environment and uses the deve environment to create the database. That
 * may change in the future
 */

/*
 * TODO
 * - use MDB2 extended to create database
 * - run the first migrations
 * - account for remote repository failure or disconnection
 */

class tasks_beachhead
{
    public $strings = array(
        0 => 'No error found',
        100 => 'No client specified',
        101 => 'No project name specified',
        200 => 'Project directory %1 already exists',
        201 => 'Project directory %1 does not exist',
        300 => 'cloning the pedantic application skeleton',
        301 => 'switching to %1 branch',
        302 => 'Creating repository',
        303 => 'Committing application',
        304 => 'Setting up the submodules',
        305 => 'Preconfiguration',
        306 => 'Configuring .htaccess',
        307 => 'Configuring config/application.php',
        308 => 'Configuring config/environments/development.php',
        309 => 'Creating the database',
        310 => 'Complete',
        /* commit messages */
        400 => 'Initial import\r\n\r\nImport by beachhead.',
        401 => 'Setting up submodules\r\n\r\nSetup by beachhead.',

    );
    public $error_no = 0;
    public $output_progress = false;

    public function run($argv) {
        $client = $argv[2];
        $project = $argv[3];

        $this->create_application($client, $project);
    }
    public function __construct() {
/* --- configuration ------------------------------------- */
        $this->public_html_path = '/home/gavin/public_html/';
        $this->public_html_url = '~gavin/';
        $this->personal_repository_url = 'git://windserver/';
        $this->pedantic_repository = $this->personal_repository_url.'pedantic';
        $this->app_skeleton_branch = 'master';
        $this->submodules = array(
            'lib' => array('repository' => 'pedantic/lib.git', 'branch' => 'version_3', 'path' => 'vendor/pedantic/lib')
        );
/* --- configuration ------------------------------------- */

    }

    public function create_application($client = null, $project = null) {

        /* check the client and project */
        if (is_null($client) || $client == '') {
            $this->error(100); //'No client specified'
            return false;
        }
        if (is_null($project) || $project == '') {
            $this->error(101); //No project name specified
            return false;
        }

        $op = $this->output_progress;

        /* generate the filename / URL friendly project_name */
        $this->project_name = str_replace('.', '_', $client.'_'.$project);

        $this->project_path = $this->public_html_path.$client.'/'.$project;

        /* does this directory already exist ? fail! */
        if (file_exists($this->project_path)) {
            $this->error(200, array('%1' => $this->project_path));
            return false;
        }

        /* expert the skeleton */
        if ($op) {
            echo $this->strings[300]."\r\n";
        }
        exec("git clone {$this->pedantic_repository}/app_skeleton.git {$this->project_path}", $output);
        if ($op) {
            $this->show_output($output); unset($output);
        }
        if ($this->app_skeleton_branch != 'master') {
            if ($op) {
                echo sprintf($this->strings[301], $this->app_skeleton_branch)."\r\n";
            }
            exec("cd {$this->project_path} ; git checkout {$this->app_skeleton_branch} ; cd ../.. ", $output);
            if ($op) {
                $this->show_output($output); unset($output);
            }
        }

        /* kill the git dir */
        exec("rm -rf {$this->project_path}/.git", $output);
        if ($op) {
            $this->show_output($output); unset($output);
        }

        /* check that the project_path exists */
        if (!file_exists($this->project_path)) {
            $this->error(201, array('%1' => $this->project_path));
            return false;
        }

        /* init a new repository */
        if ($op) {
            echo "\r\n".$this->strings[302]."\r\n";
        }
        exec("cd {$this->project_path} ; git init", $output);
        if ($op) {
            $this->show_output($output); unset($output);
        }

        /* commit */
        if ($op) {
            echo "\r\n".$this->strings[303]."\r\n";
        }
        exec("cd {$this->project_path} ; git add . ; git commit -m '".$this->strings['400']."'", $output);
        if ($op) {
            $this->show_output($output); unset($output);
        }

        /* set up the vendor exports */
        if ($op) {
            echo "\r\n".$this->strings[304]."\r\n";
        }
        foreach ($this->submodules as $submodule) {
            $repository = $submodule['repository']; 
            $branch = $submodule['branch'];
            $path = $submodule['path'];
            if ($branch == '') {
                $branch = 'master';
            }

            exec("cd {$this->project_path} ; git submodule add -b $branch {$this->personal_repository_url}$repository $path", $output, $return_status);

            if ($op) {
                $this->show_output($output); unset($output);
            }
        }

        /* final commit */
        exec("cd {$this->project_path} ; git commit -a -m '".$this->strings['401']."'", $output);
        if ($op) {
            $this->show_output($output); unset($output);
            echo "\r\n".$this->strings[305]."\r\n";
        }
        $project_url = $this->public_html_url.$client.'/'.$project;
        /* customize the new skeleton for this project */
        /*
        * .htaccess
        *    URL_TO_DEV_SITE
         */
        if ($op) {
            echo "\r\n";
            echo "\r\n".$this->strings[306]."\r\n";
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
            echo $this->strings[307]."\r\n";
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

        if ($op) {
            echo $this->strings[309]."\r\n";
            echo "\r\n\r\n";
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
            echo "\r\n".$this->strings[310];
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
