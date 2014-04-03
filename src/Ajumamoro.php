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
        return self::getStore()->get();
    }
    
    public static function add($job)
    {
        self::$store->put($job);
    }
}

