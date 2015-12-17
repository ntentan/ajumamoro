<?php

namespace ajumamoro;

use ajumamoro\Config;
use ntentan\logger\Logger;

class Scheduler
{
    public static function add(Job $job)
    {
        $store = Store::getInstance();
        $jobClass = new \ReflectionObject($job);
        $store->put(serialize($job), $jobClass->getFileName(), $job->getTag());
        return $store->lastJobId();
    }
    
    public static function connect($parameters)
    {
        Config::set('store', $parameters);
    }

    public static function getNextJob()
    {
        $store = Store::getInstance();
        $jobInfo = $store->get();
        if(is_array($jobInfo))
        {
            require_once $jobInfo['class_file_path'];
            $job = unserialize($jobInfo['object']);
            if(is_a($job, "\\ajumamoro\\Job"))
            {
                $job->setStore($store);
                $job->setId($jobInfo['id']);
                return $job;
            }
            else
            {
                Logger::error("Scheduled job is not of type \\ajumamoro\\Job.");
                $store->setStatus($jobInfo['id'], 'FAILED');
                return false;
            }
        }
        else
        {
            return false;
        }
    }    
}