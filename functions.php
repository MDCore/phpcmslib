<?php    
require_once('string_helpers.php');
require_once('form_helpers.php');

define('MENU_PAGE_PARAMETERS_TO_SKIP', '/_id$/,/^sort/,/^filter_/,/^fk$/');
$sql_phrases = array(
    'SELECT'    => ', ',
    'FROM'      => ', ',
    'WHERE'     => ' ',
    'GROUP BY'  => ', ',
    'ORDER BY'  => ' '
);

#this method does a redirect with standard parameters stripped
function redirect_with_parameters($url, $additional_parameters = '', $return_url = false)
{
    $url .= page_parameters('/^p$/,/^edit/,/^delete$/').'&'.$additional_parameters;
    if (!$return_url)
    {
        header("Location: $url");die();
    }
    else
    {
        return $url;
    }
}

function SQL_merge($array_1, $array_2)
{
    $result = array();
    global $sql_phrases;
    foreach($sql_phrases as $phrase => $join_text)
    {
        foreach(array($array_1, $array_2) as $candidate)
        {
            #candidate
                if (isset($candidate[$phrase])) 
                {
                    if (is_array($candidate[$phrase]))
                    {
                        if (is_array($result[$phrase])) 
                        {
                            $result[$phrase] = array_merge($result[$phrase], $candidate[$phrase]); 
                        }
                        else
                        {
                            $result[$phrase] = $candidate[$phrase];
                        }
                    }
                    else
                    {
                        $result[$phrase][] = $candidate[$phrase]; 
                    }
                }
        }
    }
    return $result;
}

function SQL_implode($sql_array, $prepend_phrases = true)
{
    $result = ''; 
    global $sql_phrases;
    foreach($sql_phrases as $phrase => $join_text)
    {
        $this_phrase = '';
        if ($sql_array[$phrase] != '') 
        {
            if (!is_array($sql_array[$phrase]))
            {
                if (substr(trim($sql_array[$phrase]), 0, strlen($phrase)) == $phrase) #are they appending the phrase ? e.g. passing WHERE in the string
                {
                   $sql_array[$phrase] = trim(substr(trim($sql_array[$phrase]), strlen($phrase)));
                }
                $this_phrase .= $sql_array[$phrase];
            }
            else
            {
                foreach ($sql_array[$phrase] as $item)
                {
                    $this_phrase .= trim($item).$join_text;
                }
                $this_phrase = substr($this_phrase, 0, strlen($this_phrase) - strlen($join_text));
            }

            $result .= ' ';
            #some special cases where we want to tweak the phrase
                switch ($phrase)
                {
                case 'WHERE':
                    if (substr($this_phrase, 0, 4) == 'AND ') { $this_phrase = substr($this_phrase, 4); }
                    break;
                default:
                }

            #prepend the phrase, if asked for
                if ($prepend_phrases) { $this_phrase = $phrase.' '.$this_phrase; };

            $result .= $this_phrase;
        }
    }
    return trim($result);
}

function SQL_explode($sql)
{
    #todo - add subquery checks. I'll do this when I need a subquery split, thanks. and use a recursive function!

    $result = array();
    
    $phrases = array_reverse(split(SQL_PHRASES));

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
    if (!isset($target['face'])) { $target['face'] = App::$default_face; }
    if (!isset($target['controller'])) { $target['controller'] = App::$controller->controller_name; }
    $target['controller'] = str_replace('_controller', '', $target['controller']);

    #echo "<!--";print_r($target);echo "-->";
    
    #route's are the same so just send bank emptystring
        $app_route_controller = str_replace('_controller', '', App::$route['controller']);
        if ($target['face'] == App::$route['face'] && $target['controller'] == $app_route_controller && $target['action'] == App::$route['action']) { return ''; }

    #base URL
        $url = App::$env->url.'/';

    #if the default face is the same as the target face leave the face out
        if ($target['face'] != App::$default_face) { $url .= $target['face'].'/'; }
        
    #append the controller path
        $url .= $target['controller']; 

    #no specified action?  let the controller decide
        if (isset($target['action']) && $target['action'] != '') 
        {
            $url .= '/'.$target['action'];
            if (isset($target['id']) && $target['id'] != '') 
            {
                $url .= '/'.$target['id'];
            }
        }

    return $url;
}
function href_to($path) { return url_to($path); }
?>
