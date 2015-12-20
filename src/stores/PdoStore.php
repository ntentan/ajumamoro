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
    
    public function get($jobId)
    {
        $this->getStatement->bindParam(1, $jobId);
        $this->getStatement->execute();
        if($this->getStatement->rowCount()) {
            return unserialize(stream_get_contents($this->getStatement->fetch(\PDO::FETCH_ASSOC)['object']));
        }
    }
    
    public function getNext() 
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
        $this->createTables();
        $this->insertStatement = $this->db->prepare("INSERT INTO jobs(object, status, added, class_file_path, tag, exclusive, context) VALUES(?, 'QUEUED', ?, ?, ?, ?, ?)");
        $this->retrieveStatement = $this->db->prepare("SELECT id, object, class_file_path FROM jobs WHERE status = 'QUEUED' ORDER BY added LIMIT 1");
        $this->deleteStatement = $this->db->prepare("DELETE FROM jobs WHERE id = ?");
        $this->updateStatusAndFinishTimeStatement = $this->db->prepare("UPDATE jobs SET status = ?, finished = ? WHERE id = ?");
        $this->updateStatusAndStartTimeStatement = $this->db->prepare("UPDATE jobs SET status = ?, started = ? WHERE id = ?");
        $this->setStatusStatement = $this->db->prepare('UPDATE jobs SET status = ? WHERE id = ?');
        $this->getStatement = $this->db->prepare("SELECT object FROM jobs WHERE id = ?");
    }

    /**
     * 
     * @param \ajumamoro\Job $job
     * @param string $path
     */
    public function put($job, $path) 
    {
        $date = $this->getTime();
        $this->insertStatement->bindParam(1, serialize($job), \PDO::PARAM_LOB);
        $this->insertStatement->bindParam(2, $date);
        $this->insertStatement->bindParam(3, $path);
        $this->insertStatement->bindParam(4, $job->getTag());
        $this->insertStatement->bindParam(5, $job->getExclusive());
        $this->insertStatement->bindParam(6, $job->getContext());
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
    
    private function getCondition($key, $query, &$conditions, &$bindData)
    {
        if(isset($query[$key])) {
            $conditions[] = "{$key} = ?";
            $bindData[] = $query[$key];
        }
    }

    public function getStatus($query)
    {
        $bindData = [];
        $queryString = "SELECT id, status FROM jobs WHERE ";
        if(is_numeric($query)) {
            $queryString .= "id = ?";
            $bindData[] = $query;
        } else if(is_string($query)) {
            $queryString .= "tag = ?";
            $bindData[] = $query;
        } else if(is_array($query)) {
            $conditions = [];
            $this->getCondition('tag', $query, $conditions, $bindData);
            $this->getCondition('context', $query, $conditions, $bindData);
        }
        $statement = $this->db->prepare($queryString);
        foreach($bindData as $i => $bound) {
            $statement->bindParam($i + 1, $bound);
        }
        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    abstract function createTables();
}

