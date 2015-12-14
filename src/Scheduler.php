<?php

namespace ajumamoro;

class Scheduler
{
    public static function add(Job $job)
    {
        $store = self::getStore();
        $jobClass = new \ReflectionObject($job);
        $store->put(serialize($job), $jobClass->getFileName(), $job->getTag());
        return $store->lastJobId();
    }

    public static function getNextJob()
    {
        $store = Store::getInstance();
        $jobInfo = $store->get();
        if(is_array($jobInfo))
        {
            require_once $jobInfo['class_file_path'];
            $job = unserialize($jobInfo['object']);
            if(is_a($job, "\\ajumamoro\\Ajuma"))
            {
                $job->setStore($store);
                $job->setId($jobInfo['id']);
                return $job;
            }
            else
            {
                Logger::error("Failed to execute job");
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