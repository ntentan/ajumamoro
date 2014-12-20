<?php
/**
 * 
 */
namespace ajumamoro;

class Configuration
{
    private static $configuration = array();
    
    public static function init($options)
    {
        if(isset($options['config']) && is_readable($options['config']))
        {
            self::$configuration = parse_ini_file($options['config']);
            unset($options['config']);
        }
        
        foreach($options as $key => $value)
        {
            self::$configuration[$key] = $value;
        }
        return self::$configuration;
    }
    
    public static function getConfiguration($key = null)
    {
        if($key === null)
        {
            return self::$configuration;
        }
        else
        {
            return self::$configuration[$key];
        }
    }
}

