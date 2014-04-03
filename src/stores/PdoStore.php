<?php
namespace ajumamoro\stores;

use ajumamoro\Store;

abstract class PdoStore extends Store
{
    protected $db;
    
    protected function connect()
    {
        
    }
    
    public function get() 
    {
        $this->db->query("SELECT * FROM jobs ORDER BY id DESC LIMIT 1");
    }

    public function put($job) {
        
    }

}