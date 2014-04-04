<?php
namespace ajumamoro;

abstract class Ajuma
{
    abstract public function go();
    private $attributes;
    
    public function addAttribute($attribute)
    {
        $this->attributes[] = $attribute;
    }
    
    public function getAttribute($attribute)
    {
        return $this->attributes[$attribute];
    }
    
    public function setup()
    {
        
    }
    
    public function tearDown()
    {
        
    }
}
