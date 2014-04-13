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
    
    private static function getStore()
    {
        if(self::$store === false)
        {
            self::$store = Store::factory(self::$params);
            self::$store->init();
        }
        return self::$store;
    }
    
    public static function getNextJob()
    {
        $jobInfo = self::getStore()->get();
        $job = unserialize($jobInfo['object']);
        if(is_a($job, "\\ajumamoro\\Ajuma"))
        {
            $job->setStore(self::getStore());
            $job->setId($jobInfo['id']);
            return $job;
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
        $store->put(serialize($job));
        return $store->lastJobId();
    }
}

