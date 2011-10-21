<?
/* TODO
 * - nicer output for tests. Print out each model, controller etc as its tests are being run
 *  - this probably means splitting it into seperate suites ?
 */

/* NOTE
 * This script should be shell/web agnostic.
 */
$valid_tasks = array('beachhead', 'migrate', 'test', 'create');

/*-----------------------------------------------------------------------------*/
$path_to_lib = dirname(__FILE__).'/..';
$path_to_root = $path_to_lib.'/../../..';

require ($path_to_lib.'/tasks/task_interface.php');

asort($valid_tasks);

/* $task_name and $arguments come from the shell or web task script */

/* check if running directly from lib. The beachhead does this */
if (!isset($task_name) || $task_name == '') {
    $task_name = $argv[1];
    $arguments = $argv;
    $running_from_shell = true;
}

/* clean up the arguments */
if ($running_from_shell) {
    $arguments_for_loop = $arguments;
    $arguments = array();
    foreach ($arguments_for_loop as $key => $value) {
        /* remove the -- from parameters */
        if (substr($value, 0, 2) == '--') {
            $value = substr($value, 2);
        }
        /* split the key=value items into key and value if applicable
         * otherwise leave it alone
         */
        $key_value = explode('=', $value, 2);
        if (sizeof($key_value) > 1) {
            $arguments[$key_value[0]] = $key_value[1];
        } else {
            $arguments[$key] = $value;
        }
    }
    // fake HTTP_HOST
    $_SERVER['HTTP_HOST'] = null;
}

/* deal with help specifically */
if ($task_name === 'help') {
  require ($path_to_lib.'/string_helpers.php');
  if (sizeof($arguments) > 0 && (in_array($arguments[2], $valid_tasks))) {
    require $path_to_lib.'/tasks/'.$arguments[2].'.php';
    $task_class = 'tasks_'.$arguments[2];
    $task = new $task_class;
    $task->help();

  } else {
    /* default help output */
?>
Lib Tasks
=========
Valid tasks are <?=to_sentence($valid_tasks);?>.

To get help on a specific task run task with:
   help <task name>
<?
  }
} else {
  /* load the task, and let its constructor run as an init */
  require $path_to_lib.'/tasks/'.$task_name.'.php';
  $task_name = 'tasks_'.$task_name;
  $task = new $task_name;

  /* run the app init */
  require $path_to_lib.'/init.php';
  App::$running_from_shell = $running_from_shell;

  /* run the task */
  $task->run($arguments);
}
?>
