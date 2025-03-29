<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\exceptions\AjumamoroException;
use ajumamoro\Job;
use Psr\Log\LoggerInterface;

class InlineBroker implements BrokerInterface
{
    private string $jobInfoDir;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, array $brokerConfig)
    {
        $this->jobInfoDir = $brokerConfig['jobs_path'] ?? 'jobs';
        $this->logger = $logger;
    }

    public function get()
    {
        throw new AjumamoroException("The inline broker does not require a separate worker application since it run's jobs directly within a PHP request.");
    }

    public function put($job)
    {
        $jobId = uniqid('job');
        /** @var Job $job */
        $job = unserialize($job['object']);
        $job->setLogger($this->logger);
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
        return null;
    }

    public function setStatus($job, $status)
    {
        // Prevent from writing statuses for jobs which are just queued since we're running inline anyway.
        if(is_dir($this->jobInfoDir) && is_writable($this->jobInfoDir) && $status['status'] != Job::STATUS_QUEUED) {
            file_put_contents("{$this->jobInfoDir}/$job", serialize($status));
        }
    }
}
