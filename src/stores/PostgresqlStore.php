<?php
namespace ajumamoro\stores;

class PostgresqlStore extends PdoStore
{    
    private $lastValStatement;
    
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
        parent::init(); 
        $this->db->query("create table if not exists jobs (id serial primary key, object text)");
        $this->lastValStatement = $this->db->prepare("SELECT LASTVAL() as last");
    }
    
    public function lastJobId() 
    {
        $this->lastValStatement->execute();
        $lastId = $this->lastValStatement->fetch();
        return $lastId['last'];
    }
}
