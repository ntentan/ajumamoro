<?php

namespace ajumamoro;

abstract class Store
{
    abstract public function put($job);
    abstract public function get();
    abstract public function init();
    
    public static function factory($params)
    {
        $storeDriverClass = '\\ajumamoro\\stores\\' . ucfirst($params['store']) . 'Store';
        $storeDriver = new $storeDriverClass($params);
        $storeDriver->init();
        return $storeDriver;
    }
}
