<?php
namespace ajumamoro\stores;

use ajumamoro\Config;

class PostgresqlStore extends PdoStore
{    
    private $lastValStatement;
    
    public function __construct()
    {
        $host = Config::get('store.host', 'localhost');
        $port = Config::get('store.port', '5432');
        $dbname = Config::get('store.dbname', 'ajumamoro');
        $user = Config::get('store.user', 'postgres');
        $password = Config::get('store.password');
        
        try{
            $this->db = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password");
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        catch(\PDOException $e){
            throw new \ajumamoro\Exception("Failed to connect to database: {$e->getMessage()}.");
        }
    }
    
    public function lastJobId() 
    {
        $this->lastValStatement->execute();
        $lastId = $this->lastValStatement->fetch();
        return $lastId['last'];
    }

    public function createTables()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS jobs
            (
              id serial NOT NULL,
              class_file_path character varying,
              status character varying,
              added timestamp without time zone,
              object bytea,
              finished timestamp with time zone,
              started timestamp with time zone,
              tag character varying,
              exclusive boolean,
              context character varying,
              CONSTRAINT jobs_pkey PRIMARY KEY (id)
            )"
        );
        $this->lastValStatement = $this->db->prepare("SELECT LASTVAL() as last");        
    }

}
