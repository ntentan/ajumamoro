<?php
namespace ajumamoro\stores;

use ajumamoro\Store;

abstract class PdoStore extends Store
{
    /**
     * The PDO instance
     * @var \PDO
     */
    protected $db;
    protected $insertStatement;
    protected $retrieveStatement;
    protected $deleteStatement;
    
    public function get() 
    {
        $this->retrieveStatement->execute();
        if($this->retrieveStatement->rowCount() == 1) 
        {
            $job = $this->retrieveStatement->fetch();
            return array(
                'id' => $job['id'],
                'object' => stream_get_contents($job['object'])
            );
        }
        else 
        {
            return false;
        }
    }
    
    public function init()
    {
        $this->insertStatement = $this->db->prepare("INSERT INTO jobs(object) VALUES(?)");
        $this->retrieveStatement = $this->db->prepare("SELECT * FROM jobs ORDER BY id DESC LIMIT 1");
        $this->deleteStatement = $this->db->prepare("DELETE FROM jobs WHERE id = ?");
    }

    public function put($job) 
    {   
        $this->insertStatement->bindParam(1, $job, \PDO::PARAM_LOB);
        $this->insertStatement->execute();
    }
    
    public function lastJobId() 
    {
        return $this->db->lastInsertId();
    }
    
    public function delete($job)
    {
        //$this->db->query("DELETE FROM jobs WHERE id = '{$job->getId()}'");  
        $jobId = $job->getId();
        $this->deleteStatement->bindParam(1, $jobId, \PDO::PARAM_INT);
        $this->deleteStatement->execute(array($jobId));
    }
}

