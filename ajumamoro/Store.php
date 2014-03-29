<?php
namespace ajumamoro;

abstract class Store
{
    abstract public function put($job);
    abstract public function get();
    abstract public function init();
    
    public static function factory($params)
    {
        $storeDriverClass = 'stores\\' . ucfirst($params['type']) . 'Store';
        $storeDriver = new $storeDriverClass($params);
        return $storeDriver;
    }
}
