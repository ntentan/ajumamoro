<?php
namespace ajumamoro\stores;

class PostgresqlStore extends PdoStore
{    
    public function __construct($params)
    {
        $host = isset($params['host']) ? $params['host'] : 'localhost';
        $port = isset($params['port']) ? $params['port'] : '5432';
        $dbname = isset($params['dbname']) ? $params['dbname'] : 'ajumamoro';
        $user = isset($params['user']) ? $params['user'] : 'postgres';
        $password = isset($params['password']) ? $params['password'] : 'postgres';
        
        $this->db = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password");
    }

    public function init() 
    {
        $this->db->query("create table if not exists(id serial primary key, object text)");
    }
}
