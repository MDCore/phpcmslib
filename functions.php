<?php    
require_once('string_helpers.php');
require_once('form_helpers.php');

define('MENU_PAGE_PARAMETERS_TO_SKIP', '/_id$/,/^sort/,/^filter_/,/^fk$/');

#this method does a redirect with standard parameters stripped
function redirect_with_parameters($url, $additional_parameters = '', $return_url = false)
{
    $url .= page_parameters('/^p$/,/^edit/,/^delete$/').$additional_parameters;
    if (!$return_url)
    {
        header("Location: $url");die();
    }
    else
    {
        return $url;
    }
}
function implode_sql($sql_array)
{
    $result = ''; 
    if ($sql_array['SELECT'] != '') { $result .= $sql_array['SELECT']. ' '; }
    if ($sql_array['FROM'] != '') { $result .= $sql_array['FROM']. ' '; }
    if ($sql_array['WHERE'] != '') { $result .= $sql_array['WHERE']. ' '; }
    if ($sql_array['GROUP BY'] != '') { $result .= $sql_array['GROUP BY']. ' '; }
    if ($sql_array['ORDER BY'] != '') { $result .= $sql_array['ORDER BY']. ' '; }
    return trim($result);
}

function explode_sql($sql)
{
    #todo - add subquery checks. I'll do this when I need a subquery split, thanks.

    $result = Array();
    
    $phrases = Array('ORDER BY', 'GROUP BY', 'WHERE', 'FROM', 'SELECT');

    foreach ($phrases as $phrase)

    {
        $phrasepos = strpos(strtolower($sql), strtolower($phrase));
        if (!($phrasepos === false))
        {
            $result[$phrase] = substr($sql, $phrasepos);
            $sql = substr($sql, 0, $phrasepos-1);
        }
    }
    return $result;

}
function implode_with_keys($glue, $array, $valwrap='')
{
   foreach($array AS $key => $value) {
       $ret[] = $key."=".$valwrap.$value.$valwrap;
   }
   return implode($glue, $ret);
}

function split_aliased_string($str)
{
    if (strlen($str) == 0) { return Array(); }

    $new_fields = Array();
    $str = split(',',$str);
    foreach($str as $field)
    {
        $alias = stristr($field, ' as ');
        if (false === $alias) { $alias = $field; } else {$field = substr($field, 0, strlen($field)-strlen($alias)); $alias = substr($alias, 3);}
        $new_fields[trim($field)] = trim($alias);
    }
    return $new_fields;
}

function page_parameters($except = '', $always_return_something = true, $method = 'querystring')
{
    # methods are querystring or hidden
    if ($except != '') {$except = split(',',$except);} else {$except = array();}

    $return = '';
    if (isset($_GET))
    {
        foreach ($_GET as $var => $value)
        {
            #echo $var; echo $value; echo '<br/>';
            if ($var != 'p' && $var != 'flash') {
                $matches = 0;
                if(sizeof($except) > 0)
                {
                    #check for matches
                    foreach ($except as $check)
                    {
                        #debug echo "checking for $check in $var";
                        $matches += preg_match($check, $var);
                        #debug echo "$matches<br>";
                    }
                }
                
                if ($matches == 0)
                {
                    if ($method == 'querystring') { $return .= '&'. $var .'=' .$value; }
                    if ($method == 'hidden') { $return .= "<input type=\"hidden\" name=\"$var\" value=\"$value\" />"; }
                    #debug echo "!$return!<br>";
                }
            }
        }
    }
    if ( $method == 'querystring' )
    {
        if ($always_return_something && $return == '') {$return = '?p=y';}
        elseif ($return != '')
        {
            $return = '?'.substr($return, 1);
        }
    }
return $return;
}

function debug ( $str )
{ 
    /*if (is_bool($str))
    {
        if ($str == true) { $str = "{true}"; }
        if ($str == false) { $str = "{false}"; }
    }*/
   echo "<pre>";var_dump($str);echo "</pre>";
}

# reimplement the function exists method for php < 5.1
if (!function_exists('property_exists')) {
  function property_exists($class, $property) {
   if (is_object($class))
     $class = get_class($class);

   return array_key_exists($property, get_class_vars($class));
  }
}
function dateAdd($interval,$number,$dateTime) {
       
    $dateTime = (strtotime($dateTime) != -1) ? strtotime($dateTime) : $dateTime;      
    $dateTimeArr=getdate($dateTime);
               
    $yr=$dateTimeArr[year];
    $mon=$dateTimeArr[mon];
    $day=$dateTimeArr[mday];
    $hr=$dateTimeArr[hours];
    $min=$dateTimeArr[minutes];
    $sec=$dateTimeArr[seconds];

    switch($interval) {
        case "s"://seconds
            $sec += $number;
            break;

        case "n"://minutes
            $min += $number;
            break;

        case "h"://hours
            $hr += $number;
            break;

        case "d"://days
            $day += $number;
            break;

        case "ww"://Week
            $day += ($number * 7);
            break;

        case "m": //similar result "m" dateDiff Microsoft
            $mon += $number;
            break;

        case "yyyy": //similar result "yyyy" dateDiff Microsoft
            $yr += $number;
            break;

        default:
            $day += $number;
         }      
               
        $dateTime = mktime($hr,$min,$sec,$mon,$day,$yr);
        $dateTimeArr=getdate($dateTime);
       
        $nosecmin = 0;
        $min=$dateTimeArr[minutes];
        $sec=$dateTimeArr[seconds];

        if ($hr==0){$nosecmin += 1;}
        if ($min==0){$nosecmin += 1;}
        if ($sec==0){$nosecmin += 1;}
       
        if ($nosecmin>2){     return(date("Y-m-d",$dateTime));} else {     return(date("Y-m-d G:i:s",$dateTime));}
}

function url_to($path)
{
    $url = App::$env->url;

    if (is_array($path)) { $target = $path; } else { $target = build_route($path); }
    if (!$target['face']) { $target['face'] = App::$default_face; }
    if (!$target['controller']) { $target['controller'] = App::$controller->controller_name; }

    #echo "<!--";print_r($target);echo "-->";
    
    #route's are the same so just send bank emptystring
        if ($target['face'] == App::$route['face'] && $target['controller'] == App::$route['controller'] && $target['action'] == App::$route['action']) { return ''; }

    $url = App::$env->url.'/';
    #if the default face is the same as the target face leave the face out
        if ($target['face'] != App::$default_face) { $url .= $target['face'].'/'; }
    
    $url .= str_replace('_controller', '', $target['controller']);
    #no specified action?  let the controller decide
        if ($target['action'] != '') { $url .= '/'.$target['action']; }

    return $url;
}
function href_to($path) { return url_to($path); }
?>
