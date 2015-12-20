<?php

namespace ajumamoro;

use ajumamoro\Config;

class Scheduler
{
    public function add(Job $job)
    {
        $store = Store::getInstance();
        $jobClass = new \ReflectionObject($job);
        $store->put($job, $jobClass->getFileName());
        return $store->lastJobId();
    }
    
    public static function connect($parameters)
    {
        Config::set('store', $parameters);
        return new Scheduler();
    }
    
    public function getJobStatus($query)
    {
        return Store::getInstance()->getStatus($query);
    }
    
    public function getJob($id)
    {
        $store = Store::getInstance();
        return $store->get($id);
    }
    
}