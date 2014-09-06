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
        $micro = date('s', $time) + ($time - floor($time));
        return date("Y-m-d H:i:{$micro}O", $time);
    }
    
    public function get() 
    {
        $this->retrieveStatement->execute();
        if($this->retrieveStatement->rowCount() == 1) 
        {
            $job = $this->retrieveStatement->fetch();
            return array(
                'id' => $job['id'],
                'object' => stream_get_contents($job['object']),
                'class_file_path' => $job['class_file_path']
            );
        }
        else 
        {
            return false;
        }
    }
    
    public function init()
    {
        $this->insertStatement = $this->db->prepare("INSERT INTO jobs(object, status, added, class_file_path, tag) VALUES(?, 'QUEUED', ?, ?, ?)");
        $this->retrieveStatement = $this->db->prepare("SELECT id, object, class_file_path FROM jobs WHERE status = 'QUEUED' ORDER BY added LIMIT 1");
        $this->deleteStatement = $this->db->prepare("DELETE FROM jobs WHERE id = ?");
        $this->updateStatusAndFinishTimeStatement = $this->db->prepare("UPDATE jobs SET status = ?, finished = ? WHERE id = ?");
        $this->updateStatusAndStartTimeStatement = $this->db->prepare("UPDATE jobs SET status = ?, started = ? WHERE id = ?");
        $this->setStatusStatement = $this->db->prepare('UPDATE jobs SET status = ? WHERE id = ?');
    }

    public function put($job, $path, $tag) 
    {
        $date = $this->getTime();
        $this->insertStatement->bindParam(1, $job, \PDO::PARAM_LOB);
        $this->insertStatement->bindParam(2, $date);
        $this->insertStatement->bindParam(3, $path);
        $this->insertStatement->bindParam(4, $tag);
        if(!$this->insertStatement->execute()){
            $error = $this->insertStatement->errorInfo();
            print $error[2];
        }
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
        $this->updateStatusAndFinishTimeStatement->execute(array($status, $this->getTime(), $jobId));
    }
}

