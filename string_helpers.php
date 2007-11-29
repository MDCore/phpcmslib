<?
# some useful string methods
function to_sentence($array) {
    if (sizeof($array) == 0) {return '';}
    if (sizeof($array) == 1) {return $array[0];}
    $result = '';
    for ($i=0;$i<sizeof($array)-1;$i++)
    {
        $result .= $array[$i].', ';
    }
    $result = substr($result, 0, strlen($result)-2);
    $result .= ' and '.$array[sizeof($array)-1];
    return $result;
}
function proper_nounize($str) {
    if (is_null($str) || $str == '') { return $str; }
    $str = str_replace('_', ' ', $str);$result = explode(' ', $str); #split $result on " " after converting _ to " "
    return implode(' ', array_map('proper_case', $result)); #map applies the proper_Case method to each element in $result
}
function foreign_keyize($str) {
    #debug(tableize($str));debug(singularize($str));
    return tableize($str).'_id';
    #return singularize(tableize($str)).'_id'; #auto singularization is causing probs
    
}
function humanize($str) { #todo should this worry about CamelCase ? 
    $result = '';
    $string_length = strlen($str); #this is a speedup; putting it inside the loop means it is called each time
    for ($i = 0; $i < $string_length; $i++) # todo too clunky, use a regex
    {
        $char = $str[$i]; //$char = substr($str, $i, 1);
        if ($char == strtoupper($char) && ($prev_char != strtoupper($prev_char) && $prev_char != ' '))
        {
            $result .= ' '.$char;
        }
        else
        {
            $result .= $char;
        }
        $prev_char = $char;
    }
    $result = str_replace(' _', ' ', $result); #this allows this function to work with both under_score and CamelCase
    $result = str_replace('_', ' ', $result);
    $result = str_replace('  ', ' ', $result); #todo fix this hackety hack!

    #upper case single characters
        $result = preg_replace_callback('( [a-z] )', 
       create_function(
           // single quotes are essential here,
           // or alternative escape all $ as \$
           '$matches',
           'return strtoupper($matches[0]);'
       ), $result);
    
    return proper_case($result);
}
function tableize($str) {
    return str_replace(' ', '_', $str);
}
function singularize($str) {

    if (strtolower(substr($str, -3)) == 'ses') {
        return substr($str,0, strlen($str)-2);
    }
    if (strtolower(substr($str, -3)) == 'ing') {
        return $str;
    }
    if (strtolower(substr($str, -3)) == 'ies') {
        return substr($str, 0, strlen($str)-3).'y';
    }

    if (strtolower(substr($str, -2)) == 'sses') {
        return substr($str, 0, strlen($str)-2);
    }
    if (strtolower(substr($str, -1)) == 's') {
        return substr($str, 0, strlen($str)-1);
    }

    #exceptions
    if (preg_match('/people$/', $str)) { return substr($str, 0, strlen($str)-6).'person'; }

    return $str;

}
function pluralize($str) {
    #yes, I know this is lame. I will find a decent pluralization script someday
    #todo have a pluralization cache and look up words in that cache

    if (strtolower(substr($str, -6)) == 'status') {
        return $str.'es';
    }
    if (strtolower(substr($str, -3)) == 'ing') {
        return $str;
    }
    if (strtolower(substr($str, -1)) == 'y') {
        return substr($str, 0, strlen($str)-1).'ies';
    }

    if (strtolower(substr($str, -2)) == 'ss') {
        return $str.'es';
    }
    #exceptions
    if (preg_match('/person$/', $str)) { return substr($str, 0, strlen($str)-6).'people'; }

    return $str . 's';
}
function proper_case($str) {
    $str[0] = strtoupper($str[0]);return $str;
}

function split_on_word($string, $range, $add_ellipses = false) {
    /*
     * Let me explain by way of example why I substract 1 from min and max. A string like "cats"
     * is length 4 but its array is 0-3. So if I want a min of 1 then my min must actually be 0, to compensate for the zero-based array.
     */
    $min = $range[0]-1; $max = $range[1]-1;
    
    /* if the string is less than the max then our work here is done */
        if (!isset($string{$max})) { /* speedup for: if (strlen($string) < $max) { */
            return $string;
        }

    /*
     * Now we go down our split candidates in descending order, looking for a good position to split.
     * Since we're going down in descending order, the earlier strings in the array take precedence.
     * */
    $split_candidates = array(' ', ', ', ';', '. ');

    $split_string = ''; $split_position = -1;

    foreach ($split_candidates as $candidate) {
        /* Why are we increasing the length of max ? Well, I want to account for the chance that the
         * last character is a split character or phrase. If the last character is a split 
         * character (or phrase) then we get a max length string, instead of a possibly much shorter one.
         */
        $string_candidate = substr($string, $min, ($max - $min + strlen($candidate)));

        /* we want the longest possible string; so we search backwards in the string */
        $potential_split_position = strrpos($string_candidate, $candidate);
        if ($potential_split_position > $split_position) {
            $split_position = $potential_split_position;
            $split_string = $candidate;
        }
    }
    if ($split_position == -1) { $split_position = $max - $min; }
    #var_dump($split_string); var_dump($split_position);
    $return = trim(substr($string, 0, $min+$split_position));
    if ($add_ellipses) { $return .= '&#0133;'; }
    return $return;
     
}

function value_else_na($value) {
    return value_else_none($value, 'n/a');
}
function value_else_none($value, $none_value = 'none') {
    if ($value && $value != '') {
        return $value;
    }
    else {
        return $none_value;
    }
}

function sanitize_text($text) { #todo, evaluate this name
    return nl2br(htmlspecialchars(stripslashes($text)));
}
?>
