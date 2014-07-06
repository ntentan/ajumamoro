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
        
        try{
            $this->db = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password");
        }
        catch(\PDOException $e){
            fputs(STDERR, "Failed to connect to database: {$e->getMessage()}.\n");
            die();
        }
    }

    public function init() 
    {
        parent::init(); 
        $this->db->query("CREATE TABLE IF NOT EXISTS jobs
            (
              id serial NOT NULL,
              class_file_path character varying,
              status character varying,
              added timestamp without time zone,
              object bytea,
              finished timestamp without time zone,
              CONSTRAINT jobs_pkey PRIMARY KEY (id)
            )"
        );
        $this->lastValStatement = $this->db->prepare("SELECT LASTVAL() as last");
    }
    
    public function lastJobId() 
    {
        $this->lastValStatement->execute();
        $lastId = $this->lastValStatement->fetch();
        return $lastId['last'];
    }
}
