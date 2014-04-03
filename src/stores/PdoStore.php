<?php
namespace ajumamoro\stores;

use ajumamoro\Store;

abstract class PdoStore extends Store
{
    /**
     * The PDO instance
     * @var PDO
     */
    protected $db;
    
    protected function connect()
    {
        
    }
    
    public function get() 
    {
        $job = $this->db->query("SELECT * FROM jobs ORDER BY id DESC LIMIT 1");
        if($job->rowCount == 1) return $job[0]; else return false;
        
    }

    public function put($job) 
    {
        $job = $this->db->query(
            sprintf("INSERT INTO jobs(object) VALUES('%s') ORDER BY id DESC LIMIT 1", serialize($job))
        );
    }
}