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
    protected $updateStatusAndStartTimeStatement;
    protected $updateStatusAndFinishTimeStatement;
    protected $setStatusStatement;
    
    private function getTime()
    {
        $time = microtime(true);
        $micro = round(($time - floor($time))* 1000000);
        return(date("Y-m-d H:i:s.{$micro}O", $time));
    }
    
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
        $this->insertStatement = $this->db->prepare("INSERT INTO jobs(object, status, added) VALUES(?, 'QUEUED', ?)");
        $this->retrieveStatement = $this->db->prepare("SELECT id, object FROM jobs WHERE status = 'QUEUED' ORDER BY added LIMIT 1");
        $this->deleteStatement = $this->db->prepare("DELETE FROM jobs WHERE id = ?");
        $this->updateStatusAndFinishTimeStatement = $this->db->prepare("UPDATE jobs SET status = ?, finished = ? WHERE id = ?");
        $this->updateStatusAndStartTimeStatement = $this->db->prepare("UPDATE jobs SET status = ?, started = ? WHERE id = ?");
        $this->setStatusStatement = $this->db->prepare('UPDATE jobs SET status = ? WHERE id = ?');
    }

    public function put($job) 
    {
        $date = $this->getTime();
        $this->insertStatement->bindParam(1, $job, \PDO::PARAM_LOB);
        $this->insertStatement->bindParam(2, $date);
        $this->insertStatement->execute();
    }
    
    public function lastJobId() 
    {
        return $this->db->lastInsertId();
    }
    
    public function delete($jobId)
    {
        $this->deleteStatement->bindParam(1, $jobId, \PDO::PARAM_INT);
        $this->deleteStatement->execute(array($jobId));
    }
    
    public function markStarted($jobId) 
    {
        $this->updateStatusAndStartTimeStatement->execute(array('EXECUTING', $this->getTime(), $jobId));
    }
    
    public function markFinished($jobId) 
    {
        $this->updateStatusAndFinishTimeStatement->execute(array('FINISHED', $this->getTime(), $jobId));
    }
    
    public function setStatus($jobId, $status) 
    {
        $this->updateStatusAndFinishTimeStatement->execute(array($status, $jobId));
    }
}

