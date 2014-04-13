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
    
    public function setup()
    {
        $this->store->markStarted($this->id);
        $this->__setup();
    }
    
    public function tearDown()
    {
        $this->store->markFinished($this->id);
        $this->__tearDown();
    }   
    
    public function go()
    {
        $this->__go();
    }

    protected function __setup(){}
    protected function __tearDown(){}
    
    abstract protected function __go();
}
