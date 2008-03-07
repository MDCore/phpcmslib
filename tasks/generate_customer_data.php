<?
set_time_limit(6*60*60);
$path_to_root = "../../..";
require("../lib/init.php");
require("../lib/schema_interregator.php");
require("./schema_migration.php");


$possible_domains = array('aol','google', 'yahoo', 'email', 'test', 'telkomsa', 'mymail', 'hotmail', 'msn', 'freemail', 'microsoft', 'mail', 'name', 'surname');
$possible_tlds = array('com', 'co.za', 'org.za', 'net', 'za.net', 'org', 'tv', 'biz');

#male or female
if (mt_rand(0, 100) <= 51) {
    $frequency_range = 263;
    $sex = 'female';
} 
else {
    $frequency_range = 332;
    $sex = 'male'; 
}

$AR = new AR;
$echo = false;
if ($echo ) { echo '<table>'; }
for ($i = 0; $i < 100000; $i++)
{
    #the names 
    $name = get_name($sex.'_names', $frequency_range);
    $surname = get_name('surnames', 100);
    $name = proper_nounize(strtolower($name)); $surname = proper_nounize(strtolower($surname));


    #the cell number, ZA
        $cell_number = '0'.mt_rand(7, 8).mt_rand(2, 6).mt_rand(1000000, 9999999);

    #the email address
    $email_address = strtolower($name);
        #decide on a numeric postfix
            if (mt_rand(1, 10) <= 6) { $email_address .= mt_rand(0, 999); }
        $email_address .= '@';
        #the domain name
            $domain = $possible_domains[mt_rand(1, sizeof($possible_domains))-1];
            if ($domain == 'name') { $domain = strtolower($name); }
            if ($domain == 'surname') { $domain = strtolower($surname); }
        $email_address.= $domain.'.';
        #the tld
        $email_address .= $possible_tlds[mt_rand(1, sizeof($possible_tlds))-1];

        if ($echo) {
            ?><tr><?
            ?><td><?=$name;?></td><?
            ?><td><?=$surname;?></td><?
            ?><td><?=$cell_number;?></td><?
            ?><td><?=$email_address;?></td><?
            ?></tr><?
            flush();
        }

        $sql = 'INSERT INTO large_test_data.customers (name, surname, email_address, cell_number) VALUES
("'.addslashes($name).'", "'.addslashes($surname).'", "'.addslashes($email_address).'", "'.addslashes($cell_number).'")';
$AR->db->query($sql);
echo '.';
}
if ($echo) { echo '</table>'; }

function get_name($table, $frequency_range)
{
    global $AR;
    $name_frequency = mt_rand(0, $frequency_range);
    #print_r($name_frequency);echo '<br/>';

    #first, find the highest matching frequency
        $sql = 'SELECT frequency FROM large_test_data.'.$table.' WHERE frequency >= '.($name_frequency/100).' ORDER BY frequency ASC LIMIT 1';
    #echo $sql;
        $matching_frequency = $AR->db->query($sql); $matching_frequency = $matching_frequency->fetchOne(); #$matching_frequency = $matching_frequency->frequency;
    # now get all names matching that frequency
        $sql = 'SELECT name FROM large_test_data.'.$table.' WHERE frequency = '.$matching_frequency.' ORDER by RAND() LIMIT 1';
        $name = $AR->db->query($sql);

    $name = $name->fetchOne();
    #$name = $name->name;


/*
print_r($name_frequency);echo '<br/>';
print_r($sql);echo '<br/>';
print_r($name);echo '<br/>';
echo '<br />';
*/

    return $name;
}
?>
