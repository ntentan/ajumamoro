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
        $nextJob = self::getStore()->get();
        $nextJob = unserialize($nextJob['object']);
        if(is_a($nextJob, "\\ajumamoro\\Ajuma"))
        {
            return $nextJob;
        }
        else
        {
            return false;
        }
    }
    
    public static function add($job)
    {
        $store = self::getStore();
        $store->put(serialize($job));
        return $store->lastJobId();
    }
}

