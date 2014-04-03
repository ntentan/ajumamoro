<?php

namespace ajumamoro;

abstract class Store
{
    abstract public function put($job);
    abstract public function get();
    abstract public function init();
    
    public static function factory($params)
    {
        if(!isset($params['store']) || $params['store'] == '')
        {
            fputs(STDERR, "Please specify a store type using the --store option\n");
            die();
        }
        $storeDriverClass = '\\ajumamoro\\stores\\' . ucfirst($params['store']) . 'Store';
        $storeDriver = new $storeDriverClass($params);
        $storeDriver->init();
        return $storeDriver;
    }
}
