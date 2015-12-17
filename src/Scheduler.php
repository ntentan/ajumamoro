<?php

namespace ajumamoro;

use ajumamoro\Config;

class Scheduler
{
    public function add(Job $job)
    {
        $store = Store::getInstance();
        $jobClass = new \ReflectionObject($job);
        $store->put(serialize($job), $jobClass->getFileName(), $job->getTag());
        return $store->lastJobId();
    }
    
    public function __construct($parameters)
    {
        Config::set('store', $parameters);
    }
    
    public function fetchJob($query)
    {
        
    }
   
}