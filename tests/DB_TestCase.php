<?
require_once 'application.php';
require_once 'MDB2.php' ;

/* homemade mocking */
require_once 'mocks/models.php';

class DB_TestCase extends PHPUnit_Framework_TestCase {

    public $delete_db = true; /* default true: keep mysql clean! */

    public $schema_sql = array(
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
                  `secondary_cost` double NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
              'insert' => array(
                    "INSERT INTO ARTest.products (id, category_id, name, cost, secondary_cost) VALUES (1, 1, 'Cool Shoe', 200, 50)",
                    "INSERT INTO ARTest.products (id, category_id, name, cost, secondary_cost) VALUES (2, 1, '2xCool Shoe', 400, 25)"
                )
            ),

        "category" => array('create' =>
                "create table ARTest.categories (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
              'insert' => "INSERT INTO ARTest.categories (id, name) VALUES (1, 'Shoes')"),

        "tree_table" => array('create' =>
                "CREATE TABLE ARTest.tree_tables (
                        `id` int(11) NOT NULL auto_increment,
                        `ns_root_id` int(11) NOT NULL,
                        `ns_parent_id` int(11) NOT NULL,
                        `ns_left_id` int(11) NOT NULL,
                        `ns_right_id` int(11) NOT NULL,
                        `ns_node_order` int(11) NOT NULL,
                        `ns_level` int(11) NOT NULL,
                        `name` varchar(255) NOT NULL,
                        `sum_test` float NOT NULL,
                        PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
                "
                    /*, 'insert' => "INSERT INTO ARTest.categories (id, name) VALUES (1, 'Shoes')"
                     */
                ),
        "tree_tables_lock" => array('create' =>
                "CREATE TABLE ARTest.tree_tables_locks (
                        `lockID` varchar(255) NOT NULL,
                        `lockTable` varchar(255) NOT NULL,
                        `lockStamp` varchar(255) NOT NULL
                    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;"
                    /*, 'insert' => "INSERT INTO ARTest.categories (id, name) VALUES (1, 'Shoes')"
                     */
                ),

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
              'insert' => array(
                    "INSERT INTO ARTest.finds (id, name) VALUES (1, 'Shoe')",
                    "INSERT INTO ARTest.finds (id, name) VALUES (2, 'Tin Can')",
                    "INSERT INTO ARTest.finds (id, name) VALUES (3, 'Treasure')"
                    )
                ),
        "car" => array('create' =>
                "create table ARTest.cars (
                  `id` int(11) NOT NULL auto_increment,
                  `customer_id` int(11) NOT NULL,
                  `name` varchar(255) NOT NULL,
                  PRIMARY KEY  (`id`)
              ) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
              'insert' => "INSERT INTO ARTest.cars (id, customer_id, name) VALUES (1, 1, 'A Car')"),
          );

    public $schema_definition = array(
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
            ),
            'secondary_cost' => Array(
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

        'tree_table' => Array(
            'id' => Array(
                'type' => 'int',
                'mdb2type' => 'integer',
                'length' => '4',
                'default' => ''
            ),
            'ns_root_id' => Array(
                'type' => 'int',
                'mdb2type' => 'integer',
                'length' => '4',
                'default' => ''
            ),
            'ns_parent_id' => Array(
                'type' => 'int',
                'mdb2type' => 'integer',
                'length' => '4',
                'default' => ''
            ),
            'ns_left_id' => Array(
                'type' => 'int',
                'mdb2type' => 'integer',
                'length' => '4',
                'default' => ''
            ),
            'ns_right_id' => Array(
                'type' => 'int',
                'mdb2type' => 'integer',
                'length' => '4',
                'default' => ''
            ),
            'ns_level' => Array(
                'type' => 'int',
                'mdb2type' => 'integer',
                'length' => '4',
                'default' => ''
            ),
            'ns_node_order' => Array(
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
            'sum_test' => Array(
                'type' => 'float',
                'mdb2type' => 'float',
                'default' => ''
            )
        ),

        'tree_table_lock' => Array(
            'lockID' => Array(
                'type' => 'varchar',
                'mdb2type' => 'text',
                'length' => '255',
                'default' => ''
            ),
            'lockTable' => Array(
                'type' => 'varchar',
                'mdb2type' => 'text',
                'length' => '255',
                'default' => ''
            ),
            'lockStamp' => Array(
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

    public function __construct() {
        $this->dsn = array(
            'phptype' => 'mysql',
            'username' => 'dev',
            'password' => 'dev',
            'hostspec' => 'localhost'
        );
        $this->db =& MDB2::Connect($this->dsn);
        AR::error_check($this->db);

        $this->recreate_database();
        /* put our hardcoded schema def into App */
        App::$schema_definition = $this->schema_definition;

    }
    public function __destruct() {
        if ($this->delete_db) {
            $this->db->query('DROP DATABASE IF EXISTS ARTest');
            AR::error_check($this->db);
        }
        unset($this->db);
    }
    public function recreate_database() {
        $this->db->query('DROP DATABASE IF EXISTS ARTest');
        AR::error_check($this->db);
        $this->db->query('CREATE DATABASE ARTest');
        AR::error_check($this->db);
    }
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
        //var_dump('setUp');
        $this->recreate_database();

        // setup the tables
        foreach ($this->schema_sql as $class_name => $query) {
            $this->db->query($query['create']);
            AR::error_check($this->db);

            #hack for customers
                if ($class_name == 'customer') {
                    $customers_sql = "INSERT INTO ARTest.customers (name, address, company_name)
                        SELECT concat(name,' ',surname) as name, email_address as address, surname as company_nam
                        FROM large_test_data.customers
                        LIMIT 5000";
                        $result = $this->db->query($customers_sql);
                        AR::error_check($result);
                } else {
                    if (is_array($query['insert'])) {
                        foreach ($query['insert'] as $statement) {
                            //echo $statement."\r\n";
                            $result = $this->db->query($statement);
                            AR::error_check($result);
                        }
                    }
                    else {
                        $this->db->query($query['insert']);
                        AR::error_check($this->db);
                    }
                }
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        //var_dump('tearDown');

        if (!$this->delete_db) { return false; }

        #delete all the tables
        foreach ($this->schema_sql as $class_name => $query)
        {
            if (class_exists($class_name)) {
                $model_object = new $class_name;
                $drop_sql = 'DROP TABLE '.$model_object->schema_table;
                //echo $drop_sql."\r\n";
                $this->db->query($drop_sql);
                AR::error_check($this->db);
            }
        }
    }

    /*
     * test stuff that happens on construction
     */
}
?>
