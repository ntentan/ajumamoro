<?php
namespace ajumamoro;

class Ajumamoro
{
    /**
     *
     * @var ajumamoro\Store
     */
    private static $store = false;
    private static $params = false;
    
    public static function init($params)
    {
        self::$params = $params;
    }
    
    /**
     * 
     * @return ajumamoro\Store
     */
    public static function getStore()
    {
        if(self::$store === false)
        {
            self::$store = Store::factory(self::$params);
            self::$store->init();
        }
        return self::$store;
    }
    
    public static function resetStore()
    {
        self::$store = false;
    }
    
    public static function getNextJob($loadClassFile = false)
    {
        $jobInfo = self::getStore()->get();
        if(is_array($jobInfo))
        {
            if($loadClassFile) require_once $jobInfo['class_file_path'];
            $job = unserialize($jobInfo['object']);
            if(is_a($job, "\\ajumamoro\\Ajuma"))
            {
                $job->setStore(self::getStore());
                $job->setId($jobInfo['id']);
                return $job;
            }
            else
            {
                echo "Failed to execute job ...\n";
                self::getStore()->setStatus($jobInfo['id'], 'FAILED');
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    
    public static function deleteJob($job)
    {
        self::getStore()->delete($job);
    }
    
    public static function add($job)
    {
        $store = self::getStore();
        $jobClass = new \ReflectionObject($job);
        $store->put(serialize($job), $jobClass->getFileName());
        return $store->lastJobId();
    }
}

