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
        $name = $jobClass->getName();
        $object = serialize($job);
        $jobId = $this->broker->put([
            'object' => $object, 'class' => $name
        ]);
        $this->broker->setStatus($jobId,
            ['status' => Job::STATUS_QUEUED, 'queued'=> date(DATE_RFC3339_EXTENDED)]
        );
        return $jobId;
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

    public function getJobStatus($jobId) {
        return $this->broker->getStatus($jobId);
    }

}
