<?
class db_conn extends AR
{
    function connect_to_db()
    {
        $dsn = array(
            'phptype' => 'mysql',
            'username' => 'dev',
            'password' => 'dev',
            'hostspec' => 'localhost',
            'database' => 'ARTest'
        );
        parent::connect_to_db($dsn);
        #$this->db =& MDB2::factory($dsn);
    }
}
class customer extends db_conn
{
    public $display_field = 'company_name';
    public $changelog;
    public $has_one = 'car';
}
class customer_changelog extends db_conn
{
}

class product extends db_conn
{
    public $sum_field = 'cost';
    public $belongs_to = "category";
}
class category extends db_conn
{
    public $has_many = array("products" =>
        array('ORDER BY' => 'products.name'));
}

class user extends db_conn
{
    public $has_many_through = array(
        "finds" => "user_finds"
    );
}
class user_find extends db_conn
{
    public $has_many = "users,finds";
}
class find extends db_conn
{
    public $has_many_through = array(
        "users" => "user_finds"
    );
}
class car extends db_conn
{
    public $belongs_to = 'customer';
}

class model_without_schema_def extends AR
{
}
?>
