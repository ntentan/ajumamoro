<?php

namespace ajumamoro;

abstract class Store
{
    abstract public function put($job);
    abstract public function get();
    abstract public function init();
    abstract public function lastJobId();
    abstract public function delete($jobId);
    abstract public function markStarted($jobId);
    abstract public function markFinished($jobId);
    abstract public function setStatus($jobId, $status);
    
    public static function factory($params)
    {
        if(!isset($params['store']) || $params['store'] == '')
        {
            throw new Exception('Please specify a store for the jobs.');
        }
        $storeDriverClass = '\\ajumamoro\\stores\\' . ucfirst($params['store']) . 'Store';
        $storeDriver = new $storeDriverClass($params);
        $storeDriver->init();
        return $storeDriver;
    }
}
