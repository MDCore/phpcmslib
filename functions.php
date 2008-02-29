<?php    
require('string_helpers.php');

/* todo use this define, maybe */
define('RELATED_PAGE_PARAMETERS_TO_SKIP', '/_id$/,/^sort/,/^filter_/,/^fk$/');

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
                switch ($phrase) {
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
    $str = explode(',',$str);
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
    if ($except != '') { $except = explode(',',$except); } else { $except = false; }

    $return = '';
    if (isset($_GET)) {
        foreach ($_GET as $var => $value) {
            #echo $var; echo $value; echo '<br/>';
            if ($var != 'p' && $var != 'flash') {
                $matches = 0;
                if($except) {
                    #check for matches
                    foreach ($except as $check) {
                        #debug echo "checking for $check in $var";
                        $matches += preg_match($check, $var);
                        #debug echo "$matches<br>";
                    }
                }
                
                if ($matches == 0) {
                    if ($method == 'querystring') { $return .= '&'. $var .'=' .urlencode($value); }
                    if ($method == 'hidden') { $return .= "<input type=\"hidden\" name=\"$var\" value=\"$value\" />"; }
                    #debug echo "!$return!<br>";
                }
            }
        }
    }
    if ( $method == 'querystring' ) {
        if ($always_return_something && $return == '') {
            $return = '?p=y';
        }
        elseif ($return != '') {
            $return[0] = '?';
        }
    }
return $return;
}

function debug ($str) { 
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
        if (isset($target['action']) && $target['action'] != '') {
            $url .= '/'.$target['action'];
            if (isset($target['id']) && $target['id'] != '') 
            {
                $url .= '/'.$target['id'];
            }
        }

    return $url;
}

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
        $path = explode('/', $path);
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
            if (!is_null($prefixes)) {
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

function http_header($code, $output_message_and_die = false) {
$headers = array (
       100 => "HTTP/1.1 100 Continue",
       101 => "HTTP/1.1 101 Switching Protocols",
       200 => "HTTP/1.1 200 OK",
       201 => "HTTP/1.1 201 Created",
       202 => "HTTP/1.1 202 Accepted",
       203 => "HTTP/1.1 203 Non-Authoritative Information",
       204 => "HTTP/1.1 204 No Content",
       205 => "HTTP/1.1 205 Reset Content",
       206 => "HTTP/1.1 206 Partial Content",
       300 => "HTTP/1.1 300 Multiple Choices",
       301 => "HTTP/1.1 301 Moved Permanently",
       302 => "HTTP/1.1 302 Found",
       303 => "HTTP/1.1 303 See Other",
       304 => "HTTP/1.1 304 Not Modified",
       305 => "HTTP/1.1 305 Use Proxy",
       307 => "HTTP/1.1 307 Temporary Redirect",
       400 => "HTTP/1.1 400 Bad Request",
       401 => "HTTP/1.1 401 Unauthorized",
       402 => "HTTP/1.1 402 Payment Required",
       403 => "HTTP/1.1 403 Forbidden",
       404 => "HTTP/1.1 404 Not Found",
       405 => "HTTP/1.1 405 Method Not Allowed",
       406 => "HTTP/1.1 406 Not Acceptable",
       407 => "HTTP/1.1 407 Proxy Authentication Required",
       408 => "HTTP/1.1 408 Request Time-out",
       409 => "HTTP/1.1 409 Conflict",
       410 => "HTTP/1.1 410 Gone",
       411 => "HTTP/1.1 411 Length Required",
       412 => "HTTP/1.1 412 Precondition Failed",
       413 => "HTTP/1.1 413 Request Entity Too Large",
       414 => "HTTP/1.1 414 Request-URI Too Large",
       415 => "HTTP/1.1 415 Unsupported Media Type",
       416 => "HTTP/1.1 416 Requested range not satisfiable",
       417 => "HTTP/1.1 417 Expectation Failed",
       500 => "HTTP/1.1 500 Internal Server Error",
       501 => "HTTP/1.1 501 Not Implemented",
       502 => "HTTP/1.1 502 Bad Gateway",
       503 => "HTTP/1.1 503 Service Unavailable",
       504 => "HTTP/1.1 504 Gateway Time-out"
   );

    header($headers[$code]);

    if ($output_message_and_die) {
        echo '<html><head></head><body><h1>'.$headers[$code].'</h1></body></html>';
        die();
    }
}
?>
