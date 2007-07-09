<?
# some useful string methods
function to_sentence($array)
{
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
function proper_nounize($str)
{
    $str = str_replace('_', ' ', $str);$result = split(' ', $str);#split $result on " " after converting _ to " "
    return implode(' ', array_map('proper_case', $result)); #map applies the proper_Case method to each element in $result
}
function foreign_keyize($str)
{
    #debug(tableize($str));debug(singularize($str));
    return tableize($str).'_id';
    #return singularize(tableize($str)).'_id'; #auto singularization is causing probs
    
}
function humanize($str) #todo should this worry about CamelCase ?
{
    $result = '';
    for ($i=0;$i<strlen($str);$i++) # todo too clunky, use a regex
    {
        $char = substr($str,$i,1);
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
function tableize($str)
{
    return str_replace(' ', '_', $str);
}
function singularize($str)
{

    if (strtolower(substr($str, -3)) == 'ses')
    {
        return substr($str,0, strlen($str)-2);
    }
    if (strtolower(substr($str, -3)) == 'ing')
    {
        return $str;
    }
    if (strtolower(substr($str, -3)) == 'ies')
    {
        return substr($str, 0, strlen($str)-3).'y';
    }

    if (strtolower(substr($str, -2)) == 'sses')
    {
        return substr($str, 0, strlen($str)-2);
    }
    if (strtolower(substr($str, -1)) == 's')
    {
        return substr($str, 0, strlen($str)-1);
    }

    #exceptions
    if (preg_match('/people$/', $str)) { return substr($str, 0, strlen($str)-6).'person'; }

    return $str;

}
function pluralize($str)
{
    #yes, I know this is lame. I will find a decent pluralization script someday
    #johnpipi from trax has decent implementation

    if (strtolower(substr($str, -6)) == 'status')
    {
        return $str.'es';
    }
    if (strtolower(substr($str, -3)) == 'ing')
    {
        return $str;
    }
    if (strtolower(substr($str, -1)) == 'y')
    {
        return substr($str, 0, strlen($str)-1).'ies';
    }

    if (strtolower(substr($str, -2)) == 'ss')
    {
        return $str.'es';
    }
    #exceptions
    if (preg_match('/person$/', $str)) { return substr($str, 0, strlen($str)-6).'people'; }

    return $str . 's';
}
function proper_case($str)
{
    $str[0] = strtoupper($str[0]);return $str;
}

function enquote($value) #AR::update uses this as a map function
{
    return "'$value'";
} 

function value_else_na($value)
{
    return value_else_none($value, 'n/a');
}
function value_else_none($value, $none_value = 'none')
{
    if ($value && $value != '')
    {
        return $value;
    }
    else
    {
        return $none_value;
    }
}

?>
