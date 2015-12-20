<?php
namespace ajumamoro;

abstract class Job
{
    const LOG_EMERGENCY = \ntentan\logger\Logger::EMERGENCY;
    const LOG_ALERT     = \ntentan\logger\Logger::ALERT;
    const LOG_CRITICAL  = \ntentan\logger\Logger::CRITICAL;
    const LOG_ERROR     = \ntentan\logger\Logger::ERROR;
    const LOG_WARNING   = \ntentan\logger\Logger::WARNING;
    const LOG_NOTICE    = \ntentan\logger\Logger::NOTICE;
    const LOG_INFO      = \ntentan\logger\Logger::INFO;
    const LOG_DEBUG     = \ntentan\logger\Logger::DEBUG;
    
    private $attributes;
    private $id;
    private $tag;
    private $exclusive;
    private $context;
    protected $store;
    
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    public function __get($name)
    {
        return $this->attributes[$name];
    }
        
    public function addAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }
    
    public function getAttribute($key)
    {
        return $this->attributes[$key];
    }
        
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    
    public function setStore($store)
    {
        $this->store = $store;
    }
    
    public function setTag($tag)
    {
        $this->tag = $tag;
    }
    
    public function getTag()
    {
        return $this->tag;
    }
    
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }
    
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    public function setExclusive($exclusive)
    {
        $this->exclusive = $exclusive;
    }
    
    public function getExclusive()
    {
        return $this->exclusive;
    }
    
    public function setContext($context)
    {
        $this->context = $context;
    }
    
    public function getContext()
    {
        return $this->context;
    }
    
    public function log($message, $level = self::LOG_INFO)
    {
        \ntentan\logger\Logger::log($level, $message);
    }

    public function setup(){}
    public function tearDown(){}
    
    abstract public function go();
}
