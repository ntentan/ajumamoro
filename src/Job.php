<?php

namespace ajumamoro;

use Psr\Log\LoggerInterface;

abstract class Job
{

    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILED = 'failed';
    const STATUS_FINISHED = 'finished';

    private $attributes = [];
    private $id;
    private $logger;

    protected function getAttribute($key)
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

    public function getName()
    {
        return (new \ReflectionClass($this))->getName();
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes + $this->attributes;
    }

    public function setAttribute(string $attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    public function setBroker($broker)
    {
        $this->broker = $broker;
    }

    public function setup()
    {
        
    }

    public function tearDown()
    {
        
    }

    abstract public function go();
}
