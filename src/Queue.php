<?php

namespace ajumamoro;

use ajumamoro\Config;

class Queue
{
    public function add(Job $job)
    {
        $store = Broker::getInstance();
        return $store->put($job);
    }
    
    public static function connectBroker($parameters)
    {
        Config::set('broker', $parameters);
        return new Queue();
    }
    
    public function getJobStatus($query)
    {
        return Broker::getInstance()->getStatus($query);
    }
    
    public function getJob($id)
    {
        $store = Store::getInstance();
        return $store->get($id);
    }
    
}