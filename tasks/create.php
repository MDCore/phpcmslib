<?
die('this is out of date (7 mar 2008)');

/* --- configuration ------------------------------------- */
$pedantic_repository = 'file:///home/gavin/dev/.repos/pedantic';
$app_skeleton_branch = 'trunk';
/* --- configuration ------------------------------------- */

if (!isset($path_to_root)) {
    $path_to_root = "../../..";
}

require($path_to_root.'/vendor/pedantic/lib/init.php');

$object = $argv[1]; if (is_null($object) || $object == '') { echo 'No object specified (e.g. face)'; die(); }
$object_name = $argv[2]; if (is_null($object_name) || $object_name == '') { echo 'No object name specified'; die(); }

$legitimate_objects = array('face');//controller, view

switch ($object) {
case 'face':
    create_face($object_name);
    echo "created face $object_name";
    //todo do this automagically
    echo "add $object_name to 'allowed_faces' in config/application.php';
    break;
default:
    echo "Error: object must be one of:\r\n";
    foreach($legitimate_objects as $lo) {
        echo $lo."\r\n";
    }
}

function create_face($face_name) {
    global $path_to_root;
    if (file_exists($path_to_root.'/'.$face_name)) {
        echo "face $face_name already exists.";die();
    }
    //make the face
    $result = mkdir($path_to_root.'/'.$face_name);
    if (!$result) { die("unable to create face directory $face_name"); }
    /* if we've made it this far it means we can create directories */
    $face_root = $path_to_root.'/'.$face_name.'/';
    global $pedantic_repository;
    global $app_skeleton_branch;
    system("svn export $pedantic_repository/app_skeleton/$app_skeleton_branch/site $path_to_root/$face_name --force");
}
?>
