<?
#a fake mock thingy
class Environment
{
    public $root, $url;
}
class App
{
static $face = 'site', $controller;
static $env = null;

static $schema_sql = array(
    "customer" => array('create' => 
            "CREATE TABLE ARTest.customers (
              `id` int(11) NOT NULL auto_increment,
              `created_on` datetime NOT NULL,
              `updated_on` datetime NOT NULL,
              `name` varchar(255) NOT NULL,
              `company_name` varchar(255) NOT NULL,
              `address` text NOT NULL,
              `active` char(1) NOT NULL default 'Y',
              PRIMARY KEY  (`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => "INSERT INTO ARTest.customers (id, name, company_name, address, active, created_on, updated_on) VALUES (1, 'cust 1', 'company 1', 'address 1', 'Y', now(), now())"),
    "customer_changelog" => array('create' => 
            "CREATE TABLE ARTest.customers_changelog (
              `id` int(11) NOT NULL auto_increment,
              `created_on` datetime NOT NULL,
              `updated_on` datetime NOT NULL,
              `revision` int(11) NOT NULL,
              `action` varchar(255) NOT NULL,
              `customer_id` int(11) NOT NULL,

              `name` varchar(255) NOT NULL,
              `company_name` varchar(255) NOT NULL,
              `address` text NOT NULL,
              `active` char(1) NOT NULL default 'Y',
              PRIMARY KEY  (`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => "INSERT INTO ARTest.customers_changelog (revision, action, customer_id, id, name, company_name, address, active, created_on, updated_on) VALUES (1, 'insert', 1, 1, 'cust 1', 'company 1', 'address 1', 'Y', now(), now())"),
    "product" => array('create' => 
            "create table ARTest.products (
              `id` int(11) NOT NULL auto_increment,
              `category_id` int(11) NOT NULL,
              `name` varchar(255) NOT NULL,
              `cost` double NOT NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => array(
                "INSERT INTO ARTest.products (id, category_id, name, cost) VALUES (1, 1, 'Cool Shoe', 200)",
                "INSERT INTO ARTest.products (id, category_id, name, cost) VALUES (2, 1, '2xCool Shoe', 400)"
            )
        ),
    "category" => array('create' => 
            "create table ARTest.categories (
              `id` int(11) NOT NULL auto_increment,
              `name` varchar(255) NOT NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => "INSERT INTO ARTest.categories (id, name) VALUES (1, 'Shoes')"),
    "user" => array('create' => 
            "create table ARTest.users (
              `id` int(11) NOT NULL auto_increment,
              `name` varchar(255) NOT NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => "INSERT INTO ARTest.users (id, name) VALUES (1, 'Jim')"),
    "user_find" => array('create' => 
            "create table ARTest.user_finds (
              `id` int(11) NOT NULL auto_increment,
              `find_id` int(11) NOT NULL,
              `user_id` int(11) NOT NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => "INSERT INTO ARTest.user_finds (id, find_id, user_id) VALUES (1, 1, 1)"),
    "find" => array('create' => 
            "create table ARTest.finds (
              `id` int(11) NOT NULL auto_increment,
              `name` varchar(255) NOT NULL,
              PRIMARY KEY  (`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => "INSERT INTO ARTest.finds (id, name) VALUES (1, 'Shoe')"),
    "car" => array('create' => 
            "create table ARTest.cars (
              `id` int(11) NOT NULL auto_increment,
              `customer_id` int(11) NOT NULL,
              `name` varchar(255) NOT NULL,
              PRIMARY KEY  (`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
          'insert' => "INSERT INTO ARTest.cars (id, customer_id, name) VALUES (1, 1, 'A Car')"),
      );

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
    'product' => Array(
	'id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'category_id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'name' => Array(
		'type' => 'varchar',
		'mdb2type' => 'text',
		'length' => '255',
		'default' => ''
    ),
	'cost' => Array(
		'type' => 'float',
		'mdb2type' => 'float',
		'default' => ''
        )
    ),
    'category' => Array(
	'id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'name' => Array(
		'type' => 'varchar',
		'mdb2type' => 'text',
		'length' => '255',
		'default' => ''
            ),
        ),
    'user' => Array(
	'id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'name' => Array(
		'type' => 'varchar',
		'mdb2type' => 'text',
		'length' => '255',
		'default' => ''
            ),
        ),
    'find' => Array(
	'id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'name' => Array(
		'type' => 'varchar',
		'mdb2type' => 'text',
		'length' => '255',
		'default' => ''
            ),
        ),
    'user_find' => Array(
	'id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'find_id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'user_id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
        ),

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
            ),
            ),
            
    'customer_changelog' => Array(
	'id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'revision' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'customer_id' => Array(
		'type' => 'int',
		'mdb2type' => 'integer',
		'length' => '4',
		'default' => ''
	),
	'action' => Array(
		'type' => 'varchar',
		'mdb2type' => 'text',
		'length' => '255',
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
