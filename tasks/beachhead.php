#!/usr/bin/php
<?
/*
 * TODO
 * - unit tests
 */
/* --- usage ------------------------------------- */
/* beachhead.php CLIENT_NAME PROJECT_NAME */

/* --- configuration ------------------------------------- */
$public_html_path = '/home/gavin/public_html/';
$public_html_url = '~gavin/';
$personal_repository_url = 'git://windserver/';
$pedantic_repository = $personal_repository_url.'pedantic';
$app_skeleton_branch = 'master';
$submodules = array(
    'lib' => array('repository' => 'pedantic/lib.git', 'branch' => 'version_3', 'path' => 'vendor/pedantic/lib')
);
/* --- configuration ------------------------------------- */

/* get the args */
$client = $argv[1]; if (is_null($client) || $client == '') { echo 'No client specified'; die(); }
$project_name = $argv[2]; if (is_null($project_name) || $project_name == '') { echo 'No project name specified'; die(); }

$project_path = $public_html_path.$client.'/'.$project_name;

/* does this directory already exist ? fail! */
if (file_exists($project_path)) {
    die('project directory '.$project_path.' already exists');
}

/* expert the skeleton */
echo "cloning the pedantic application skeleton\r\n";
system("git clone $pedantic_repository/app_skeleton.git {$project_path}");
if ($app_skeleton_branch != 'master') {
    echo "switching to $app_skeleton_branch branch\r\n";
    system("cd $project_path; git checkout $app_skeleton_branch ; cd ../.. ");
}

/* kill the git dir */
system("rm -rf $project_path/.git");

/* init a new repository */
echo "\r\nCreating repository\r\n";
system("cd $project_path ; git init");
/* commit */
echo "\r\nCommiting application\r\n";
system("cd $project_path ; git add . ; git commit -m 'Initial import\r\n\r\nImport by beachhead script.'");

/* set up the vendor exports */
echo "\r\nSetting up the submodules\r\n";
foreach ($submodules as $submodule) {
    $repository = $submodule['repository']; 
    $branch = $submodule['branch'];
    $path = $submodule['path'];
    if ($branch == '') {
        $branch = 'master';
    }

    system("cd $project_path ; git submodule add -b $branch $personal_repository_url$repository $path");
}

/* final commit */
system("cd $project_path ; git commit -a -m 'Setting up submodules\r\n\r\nSetup by beachhead script.'");

/* preconfiguration */
require(dirname(__FILE__).'/../functions.php');

echo "\r\nPreconfiguration\r\n";
$project_name = str_replace('.', '_', $client.'_'.$project_name);
$project_url = $public_html_url.$client.'/'.$project_name;
/* customize the new skeleton for this project */
/*
* .htaccess
*    URL_TO_DEV_SITE
 */
echo "Configuring .htaccess\r\n";
replace_keywords_in_file($project_path.'/.htaccess',
    array('URL_TO_DEV_SITE' => $project_url)
);

/*
 * config/application
*   YOUR_APP_NAME
*   PASSWORD_SALT
*/
echo "Configuring config/application.php\r\n";
replace_keywords_in_file($project_path.'/config/application.php',
    array(
        'YOUR_APP_NAME' => $project_name,
        'RANDOM_PASSWORD_SALT' => random_string(32, 'abcdefghijklmnopqrstuvwxyz!@#$%^&*+-=')
    )
);

/* config/environments/development.php
*   DB_NAME
*   SITE
*/
echo "Configuring config/environments/development.php\r\n";
replace_keywords_in_file($project_path.'/config/environments/development.php',
    array(
        'DB_NAME' => $project_name,
        'DEV_PATH' => $project_path,
        'DEV_URL' => $project_url
    )
);
# todo create the database

echo "\r\nComplete";

function replace_keywords_in_file($filename, $keyword_array) {
    $source = file_get_contents($filename);
    foreach($keyword_array as $keyword => $value) {
        $source = str_replace($keyword, $value, $source);
    }
    file_put_contents($filename, $source);
}
?>
