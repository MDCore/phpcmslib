<?
if (isset($config['profiling']) && $config['profiling'] == true) {
    apd_set_pprof_trace();
}

if (!isset($path_to_root)) {
    $path_to_root = '../../..';
}

/* get the name of any dispatcher passed in the querystring */
$dispatcher = '';
if (isset($_GET['dispatcher']))
{
    $dispatcher = $_GET['dispatcher'];
}
/* use the default dispatcher if none is passed */
switch ($dispatcher) {
case 'stylesheet':
case 'stylesheet_dispatcher':
    $dispatcher = 'stylesheet_dispatcher';
    break;
case '':
case 'dispatcher':
default:
    $dispatcher = 'dispatcher';
    break;
}
    
/* load this dispatcher class */
$dispatcher_path = $path_to_root.'/vendor/pedantic/lib/'.$dispatcher.'.php';
require($dispatcher_path);

/* initialize the application */
include($path_to_root.'/vendor/pedantic/lib/init.php');

/* do the dispatching process */
    $dispatch = new $dispatcher; $dispatch->process();
?>
