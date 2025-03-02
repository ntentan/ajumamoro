<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\exceptions\AjumamoroException;
use ajumamoro\Job;

class InlineBroker implements BrokerInterface
{
//    private $status;
    private string $jobInfoDir;

    public function __construct(array $brokerConfig)
    {
        $this->jobInfoDir = $brokerConfig['jobs_path'] ?? 'jobs';
    }

    public function get()
    {
        throw new AjumamoroException("The inline broker does not require a separate worker application since it run's jobs directly within a PHP request.");
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
        // Prevent from writing statuses for jobs which are just queued since we're running inline anyway.
        if(is_dir($this->jobInfoDir) && is_writable($this->jobInfoDir) && $status['status'] != Job::STATUS_QUEUED) {
            file_put_contents("{$this->jobInfoDir}/$job", serialize($status));
        }
    }
}
