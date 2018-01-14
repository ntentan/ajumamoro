<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;

class InlineBroker implements BrokerInterface
{
    private $status;
    private $jobInfoDir;

    public function __construct($jobInfoDir = 'jobs')
    {
        $this->jobInfoDir = $jobInfoDir;
    }

    public function get()
    {
    }

    public function put($job)
    {
        $jobId = uniqid('job');
        $job = unserialize($job['object']);
        $job->setup();
        $job->go();
        $job->tearDown();
        return $jobId;
    }

    public function getStatus($job)
    {
        $path = "{$this->jobInfoDir}/$job";
        if(file_exists($path)) {
            return file_get_contents($path);
        }
    }

    public function setStatus($job, $status)
    {
        file_put_contents("{$this->jobInfoDir}/$job", serialize($status));
    }
}
