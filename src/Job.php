<?php

namespace ajumamoro;

use Psr\Log\LoggerInterface;

abstract class Job
{

    private $attributes;
    private $id;
    protected $broker;
    protected $logger;

    protected function getAttribute($key) {
        return $this->attributes[$key];
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }
    
    public function getName() {
        return (new \ReflectionClass($this))->getName();
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }
    
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function setup() {
        
    }

    public function tearDown() {
        
    }

    abstract public function go();
}
