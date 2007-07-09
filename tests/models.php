<?
class customer extends AR
{
    function connect_to_db()
    {
        $dsn = array(
            'phptype' => 'mysql',
            'username' => 'root',
            'password' => '',
            'hostspec' => 'localhost',
            'database' => 'ARTest'
        );
        $this->db =& MDB2::Connect($dsn);
    }
}
class model_without_schema_def extends AR
{
}

#a fake mock thingy
class App
{
    function error_check($result, $die_on_error = true)
    {
        if (PEAR::isError($result) || MDB2::isError($result)) {
            if ($die_on_error)
            {
                die('<pre>'.$result->getMessage().' - '.$result->getUserinfo()).'</pre>';
            }
            else
            {
                return $result->code;
            }
        }
    }
    
    static $schema_definition = array(
    'customer' => Array(
	'id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'created_on' => Array(
		'type' => 'datetime',
		'mdb2type' => 'timestamp',
		'length' => '',
		'default' => ''
	),
	'updated_on' => Array(
		'type' => 'datetime',
		'mdb2type' => 'timestamp',
		'length' => '',
		'default' => ''
	),
	'name' => Array(
		'type' => 'varchar',
		'mdb2type' => 'text',
		'length' => '255',
		'default' => ''
	),
	'company_name' => Array(
		'type' => 'varchar',
		'mdb2type' => 'text',
		'length' => '255',
		'default' => ''
	),
	'address' => Array(
		'type' => 'text',
		'mdb2type' => 'text',
		'length' => '',
		'default' => ''
	),
	'active' => Array(
		'type' => 'char',
		'mdb2type' => 'text',
		'length' => '1',
		'default' => 'Y'
	)
        ));

}
?>
