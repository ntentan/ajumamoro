<?php

namespace ajumamoro;

abstract class Store
{
    private static $instance;
    
    abstract public function put($job, $path);
    abstract public function getNext();
    abstract public function get($jobId);
    abstract public function init();
    abstract public function lastJobId();
    abstract public function delete($jobId);
    abstract public function markStarted($jobId);
    abstract public function markFinished($jobId);
    abstract public function setStatus($jobId, $status);
    abstract public function getStatus($query);
    
    private static function factory()
    {
        if(!Config::get('store') || !Config::get('store.driver'))
        {
            throw new Exception('Please specify a store for the jobs.');
        }
        $storeDriverClass = '\\ajumamoro\\stores\\' . ucfirst(Config::get('store.driver')) . 'Store';
        $storeDriver = new $storeDriverClass();
        $storeDriver->init();
        return $storeDriver;
    }
    
    /**
     *
     * @return ajumamoro\Store
     */
    public static function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = Store::factory();
            self::$instance->init();
        }
        return self::$instance;
    }  
    
    public static function reset()
    {
        self::$instance = null;
    }    
    
    public static function setParameters($parameters)
    {
        self::$parameters = $parameters;
    }
}
