<?php

namespace ajumamoro;

use Psr\Log\LoggerInterface;

abstract class Job
{

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILED = 'failed';
    const STATUS_FINISHED = 'finished';
    
    private $attributes;
    private $id;
    private $logger;
    private $container;

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
    
    protected function getLogger() {
        return $this->logger;
    }
    
    public function setContainer($container) {
        $this->container = $container;
    }
    
    protected function getContainer() {
        return $this->container;
    }
    
    public function setBroker($broker) {
        $this->broker = $broker;
    }

    public function setup() {
        
    }

    public function tearDown() {
        
    }

    abstract public function go();
}
