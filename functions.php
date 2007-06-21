<?php    
require_once('string_helpers.php');
require_once('form_helpers.php');

define('MENU_PAGE_PARAMETERS_TO_SKIP', '/^page$/,/^action/,/_id$/,/^sort/,/^filter_/,/^fk$/');

function redirect_from_handler($additional_parameters = '', $just_the_url = false) #rename to redirect_with_parameters
{
    $url = "index.php".page_parameters('/^p$/,/^action/,/^edit/,/^delete$/,/^returnaction/').$additional_parameters;
    if (!$just_the_url)
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

function menu_item($name, $page_name = null, $highlight = false)
{
    if (!$page_name) {$page_name = $name;}

    ?><a href="<?=page_parameters(MENU_PAGE_PARAMETERS_TO_SKIP);?>&page=<?=$page_name?>"><?
    if ( $highlight ) { echo '<strong>'; }
    echo humanize($name);?></a> | <?
    if ( $highlight ) { echo '</strong>'; }

}

// Added to return and not echo

function menu_item_rtn($name, $page_name = null, $highlight = false)
{
    if (!$page_name) {$page_name = $name;}

    ?>
	<li><a href="<?=page_parameters(MENU_PAGE_PARAMETERS_TO_SKIP);?>&page=<?=$page_name?>">
	<?
    if ( $highlight ) { echo '<strong>'; }
    echo humanize($name);?></a><?
    if ( $highlight ) { echo '</strong>'; }
	echo '</li>';
}


function menu_link($name, $page_name=null, $menu_name = null)
{
    if (is_null($menu_name)) {$menu_name = $name;}
    ?><a href="?menu=<?=$menu_name?><?
    if ($page_name) { echo "&page=$page_name";}
    ?>"><?=humanize($name)?></a> |  <?
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
        if ($always_return_something && $return == '') {$return = '?p=y';} else {
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
?>
