<?php    
require_once('string_helpers.php');
require_once('form_helpers.php');

define('MENU_PAGE_PARAMETERS_TO_SKIP', '/_id$/,/^sort/,/^filter_/,/^fk$/');

#this method does a redirect with standard parameters stripped
function redirect_with_parameters($url, $additional_parameters = '', $return_url = false) {
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

function SQL_implode($sql_array, $prepend_phrases = true) {
    $result = ''; 
    foreach(AR::$sql_phrases as $phrase => $join_text)
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
function SQL_explode($sql) {
    #todo - add subquery checks. I'll do this when I need a subquery split, thanks. and use a recursive function!
    $result = array();
    
    /*
     * todo tokenize this sql statement eg: 
    *           $sql = SELECT a, b, (SELECT cats from foo where foo.id = blah.id) as meh FROM blah"
     * tokenize should do this:
     *          $sql = SELECT a, b, (~#1#~) as meh FROM blah
     */
    foreach (array_reverse(AR::$sql_phrases) as $phrase => $join_text)
    {
        $phrasepos = strpos(strtolower($sql), strtolower($phrase));
        if (!($phrasepos === false))
        {

            switch ($phrase)
            {
            case 'WHERE':
                $result[$phrase] = array(substr($sql, $phrasepos + strlen($phrase)+1));
                break;
            default:
                $result[$phrase] = substr($sql, $phrasepos + strlen($phrase)+1);
                if (strpos($sql, $join_text) > 1)
                {
                    #split this phrase into an array
                    $result[$phrase] = explode($join_text, $result[$phrase]);
                }
            }
            $sql = substr($sql, 0, $phrasepos-1);
        }
    }
    return array_reverse($result);

}
function SQL_merge($array_1, $array_2) {
    $result = array();
    foreach(AR::$sql_phrases as $phrase => $join_text)
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

function implode_with_keys($glue, $array, $valwrap='') {
   foreach($array AS $key => $value) {
       $ret[] = $key."=".$valwrap.$value.$valwrap;
   }
   return implode($glue, $ret);
}

function split_aliased_string($str) {
    /* todo: is this method necessary now that sql queries are being broken up into arrays ?
     */
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

function page_parameters($except = '', $always_return_something = true, $method = 'querystring') {
    # methods are querystring or hidden
    if ($except != '') { $except = split(',',$except); } else { $except = array(); }

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
                    if ($method == 'querystring') { $return .= '&'. $var .'=' .urlencode($value); }
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

function debug ( $str ) { 
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
function date_add($interval,$number,$dateTime) {
       
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
       
        #if ($nosecmin>2){     return(date("Y-m-d",$dateTime));} else { return(date("Y-m-d G:i:s",$dateTime));}
        return $dateTime;
}

function url_to($path, $include_base = true, $explicit_path = false) {
    //$url = App::$env->url;

    if (is_array($path)) { $target = $path; } else { $target = route_from_path($path); }
    if (!isset($target['face'])) { $target['face'] = App::$route['face']; }
    if (!isset($target['controller'])) { $target['controller'] = App::$controller->controller_name; }
    $target['controller'] = str_replace('_controller', '', $target['controller']);

    #echo "<!--";print_r($target);echo "-->";
    
    #route's are the same so just send bank emptystring
        $app_route_controller = str_replace('_controller', '', App::$route['controller']);
        if (!$explicit_path) {
            if ($target['face'] == App::$route['face'] && $target['controller'] == $app_route_controller && $target['action'] == App::$route['action'] && $target['id'] == App::$route['id']) { return ''; }
        }

    #base URL
        if ($include_base) {
            $url = App::$env->url;
        }
        $url .= '/';

    #if the default face is the same as the target face leave the face out
        if ($target['face'] != App::$default_face || $explicit_path) { $url .= $target['face'].'/'; }
        
    #append the controller path
        $url .= $target['controller']; 

    # warn if action is specified without target
        if ((!isset($target['action']) | $target['action'] == '') && (isset($target['id']) && $target['id'] != '')) {
            trigger_error('id specified in route without action', E_USER_WARNING); 
        }

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

function route_from_path($path) {
    #default route
    $result = array(
        'face' => '',
        'controller' => 'default_controller',
        'action' => '',
        'id' => ''
        );

    $result['face'] = App::$route['face'];
    if (!$result['face']) { $result['face'] = App::$default_face; }
        
    if ($path) {
        $path = split('/', $path);
        if (!in_array($path[0], App::$allowed_faces) && App::$default_face) #i.e. the first param is not a face, or not an allowed face, but we have a default face set
        {
            $result['controller'] = $path[0].'_controller';

            if (isset($path[1])) { $result['action'] = $path[1]; }
            if (isset($path[2])) { $result['id'] = $path[2]; }
        }
        else {
            $result['face'] = $path[0];
            #verify the face
            if (!in_array($result['face'], App::$allowed_faces)) { trigger_error('Face <i>'.$result['face'].'</i> not found', E_USER_ERROR); }

            if ($path[1]) {$result['controller'] = $path[1].'_controller';}

            if (isset($path[2])) { $result['action'] = $path[2]; }
            if (isset($path[3])) { $result['id'] = $path[3]; }
        }
    }

    return $result;
}

function as_hiddens($collection, $prefixes = null) {
    $these_prefixes = $prefixes;
    $result = '';
    foreach ($collection as $name => $value)
    {
        if (is_array($value))
        {
            if ($prefixes != null) { $these_prefixes[] = $name; } else { $these_prefixes = array($name); }
            #var_dump($these_prefixes);
            $result .= as_hiddens($value, $these_prefixes);
            #$prefixes = null;
            #print_r($value);die();
        }
        else
        {
            #echo 'prefixes:';var_dump($prefixes);echo "\r\n"; 
            if (!is_null($prefixes))
            {
                $cnt = 0;
                $this_prefix = '';
                foreach ($prefixes as $prefix)
                {
                    $cnt++;
                    if ($cnt == 1)
                    {
                        $this_prefix .= $prefix;
                    }
                    else
                    {
                        $this_prefix .= '['.$prefix.']';
                    }
                }
                $name = $this_prefix.'['.$name.']';
                #if ($cnt > 1) { $name .= '[]'; }; #append an array thing to this because there 
            }
            $this_hidden = '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
            #var_dump($this_hidden);
            $result .= $this_hidden;
        }
    }
    return $result;
}


function random_string($length = 9, $allowed_characters = "abcdefghijkmnopqrstuvwxyz023456789") {
    $i = 0;
    $string = '' ;

    for ($i=0; $i < $length; $i++) {
        $string .= substr($allowed_characters, rand() % strlen($allowed_characters), 1);
    }

    return $string;
} 
?>
