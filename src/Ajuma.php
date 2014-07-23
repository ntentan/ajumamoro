<?php
namespace ajumamoro;

abstract class Ajuma implements \ArrayAccess
{
    private $attributes;
    private $id;
    protected $store;
    
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    public function __get($name)
    {
        return $this->attributes[$name];
    }
    
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset,$value)
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
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

    public function setup(){}
    public function tearDown(){}
    
    abstract public function go();
}
