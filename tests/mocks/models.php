<?
class db_conn extends AR
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
class customer extends db_conn
{
    public $display_field = 'company_name';
    public $changelog;
}
class customer_changelog extends db_conn
{
}

class product extends db_conn
{
    public $sum_field = 'cost';
    public $has_one = "category";
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

class model_without_schema_def extends AR
{
}
?>
