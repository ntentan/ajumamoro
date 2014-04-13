<?php
namespace ajumamoro;

abstract class Ajuma
{
    private $attributes;
    private $id;
    protected $store;
    
    public function addAttribute($attribute)
    {
        $this->attributes[] = $attribute;
    }
    
    public function getAttribute($attribute)
    {
        return $this->attributes[$attribute];
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
