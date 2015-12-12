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
        $jobInfo = self::getStore()->get();
        if(is_array($jobInfo))
        {
            require_once $jobInfo['class_file_path'];
            $job = unserialize($jobInfo['object']);
            if(is_a($job, "\\ajumamoro\\Ajuma"))
            {
                $job->setStore(self::getStore());
                $job->setId($jobInfo['id']);
                return $job;
            }
            else
            {
                Logger::error("Failed to execute job");
                self::getStore()->setStatus($jobInfo['id'], 'FAILED');
                return false;
            }
        }
        else
        {
            return false;
        }
    }    
}