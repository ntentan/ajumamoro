<?php

namespace ajumamoro;

use ntentan\config\Config;
use ntentan\logger\Logger;

abstract class Broker
{
    private static $instance;
    
    abstract public function put($job);
    abstract public function get();
    abstract public function init();
    
    private static function factory()
    {
        if(!Config::get('ajumamoro:broker') || !Config::get('ajumamoro:broker.driver'))
        {
            throw new Exception('Please specify a broker for the jobs.');
        }
        $storeDriverClass = '\ajumamoro\brokers\\' . ucfirst(Config::get('ajumamoro:broker.driver')) . 'Broker';
        $storeDriver = new $storeDriverClass();
        $storeDriver->init();
        return $storeDriver;
    }
    
    /**
     *
     * @return ajumamoro\Store
     */
    public static function getInstance()
    {
        $delay = 30;
        while(self::$instance === null)
        {
            try{
                self::$instance = self::factory();
                self::$instance->init();                
            } catch (exceptions\BrokerConnectionException $e) {
                Logger::alert("{$e->getMessage()}. Retrying in $delay seconds.");
                sleep($delay);
                $delay *= 2;
            }
        }
        Logger::info("Succesfully connected to broker");
        return self::$instance;
    }  
    
    public static function reset()
    {
        self::$instance = null;
    }    
    
    public static function setParameters($parameters)
    {
        self::$parameters = $parameters;
    }
}
