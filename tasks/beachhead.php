#!/usr/bin/php
<?
/* --- usage ------------------------------------- */
/* beachhead.php CLIENT_NAME PROJECT_NAME */

/* --- configuration ------------------------------------- */
//$public_html = '/home/gavin/public_html';
$personal_repository_url = 'git://windserver/';
//$personal_repository_path = '/home/gavin/dev/.repos';
$pedantic_repository = $personal_repository_url.'pedantic';
$app_skeleton_branch = 'master';
$submodules = array(
    'lib' => array('repository' => 'pedantic/lib.git', 'branch' => 'version_3', 'path' => 'vendor/pedantic/lib')
);
/* --- configuration ------------------------------------- */


/* get the args */
$client = $argv[1]; if (is_null($client) || $client == '') { echo 'No client specified'; die(); }
$project_name = $argv[2]; if (is_null($project_name) || $project_name == '') { echo 'No project name specified'; die(); }

$project_directory = './'.$client.'/'.$project_name;

/* does this directory already exist ? fail! */
if (file_exists($project_directory)) {
    die('project directory '.$project_directory.' already exists');
}

/* expert the skeleton */
echo "cloning the pedantic application skeleton\r\n";
system("git clone $pedantic_repository/app_skeleton.git {$project_directory}");
if ($app_skeleton_branch != 'master') {
    echo "switching to $app_skeleton_branch branch\r\n";
    system("cd $project_directory; git checkout $app_skeleton_branch ; cd ../.. ");
}

/* kill the git dir */
system("rm -rf $project_directory/.git");

/* init a new repository */
echo "\r\ncreating repository\r\n";
system("cd $project_directory ; git init");
/* commit */
echo "\r\ncommiting application\r\n";
system("cd $project_directory ; git add . ; git commit -m 'Initial import\r\n\r\nImport by beachhead script.'");

/* set up the vendor exports */
echo "\r\nsetting up the submodules\r\n";
foreach ($submodules as $submodule) {
    $repository = $submodule['repository']; 
    $branch = $submodule['branch'];
    $path = $submodule['path'];
    if ($branch == '') {
        $branch = 'master';
    }

    system("cd $project_directory ; git submodule add -b $branch $personal_repository_url$repository $path");
}

/* final commit */
system("cd $project_directory ; git commit -a -m 'Setting up submodules\r\n\r\nSetup by beachhead script.'");

/* customize the new skeleton for this project */
/*
* .htaccess
*    URL_TO_DEV_SITE
 */

/*
 * config/application
*   YOUR_APP_NAME
*   PASSWORD_SALT
*/

/* config/environments/development.php
*   DB_NAME
*   SITE
*/
#create the database
#/* client_project - replacing dot's with underscores */

echo "\r\ncomplete";
?>