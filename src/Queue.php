<?php

namespace ajumamoro;

use ntentan\panie\Container;
use ntentan\utils\Text;

class Queue
{
    private $broker;
    
    public function __construct(BrokerInterface $broker) {
        $this->broker = $broker;
    }
    
    public function add(Job $job) {
        $jobClass = new \ReflectionClass($job);
        $path = $jobClass->getFileName();
        $name = $jobClass->getName();
        $object = serialize($job);
        return $this->broker->put([
            'path' => $path, 'object' => $object, 'class' => $name
        ]);
    }

    public static function setup(Container $container, $config) {
        $container->bind(BrokerInterface::class)->to(
            function($container) use ($config) {
                $brokerClass = sprintf(
                    "\\ajumamoro\\brokers\\%sBroker", 
                    Text::ucamelize($config['broker'])
                );
                $broker = $container->resolve(
                    $brokerClass, 
                    ['config' => $config[$config['broker']]]
                );
                return $broker;
            }
        );        
    }

    public function getJobStatus($query) {
        return Broker::getInstance()->getStatus($query);
    }

}
