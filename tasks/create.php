<?
/*
 * TODO
 * - Documentation
 * - Add face to $allowed_faces in conf/app
 * - NBNB controllers/views etc
 */
class tasks_create
{
  private $legitimate_objects = array('face');

  public function run($arguments) {
    $object = $arguments[2]; if (is_null($object) || $object == '') { echo 'No object specified (e.g. face)'; die(); }
    $object_name = $arguments[3]; if (is_null($object_name) || $object_name == '') { echo 'No object name specified'; die(); }

    switch ($object) {
    case 'face':
      $this->create_face($object_name);
      echo "created face $object_name\r\n";
      //todo do this automagically
      echo "\r\nTo use this face add $object_name to \$allowed_faces in config/application.php";
      break;
    default:
      echo "Error: object must be one of:\r\n";
      foreach($legitimate_objects as $lo) {
          echo $lo."\r\n";
      }
    }
  }

  private $face_structure = array('controllers', 'views', 'layouts', 'assets/images', 'assets/scripts', 'assets/stylesheets', 'assets/swfs');
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
    foreach ($this->face_structure as $folder) {
      mkdir($path_to_root.'/'.$face_name.'/'.$folder, 0777, true); //true is like mkdir -p
    }
  }
}
?>
