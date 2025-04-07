<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\exceptions\AjumamoroException;
use ajumamoro\JobInfo;
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

    public function get(): JobInfo
    {
        throw new AjumamoroException("The inline broker does not require a separate worker application since it run's jobs directly within a PHP request.");
    }

    public function put(JobInfo $job): string
    {
        $jobId = uniqid('job');
        /** @var Job $job */
        $jobInstance = unserialize($job->serialized);
        $jobInstance->setLogger($this->logger);
        $jobInstance->setup();
        $jobInstance->go();
        $jobInstance->tearDown();
        return $jobId;
    }

    public function getStatus(string $jobId): array
    {
        $path = "{$this->jobInfoDir}/$jobId";
        if(file_exists($path)) {
            return file_get_contents($path);
        }
        return [];
    }

    public function setStatus(string $jobId, array $status): void
    {
        // Prevent from writing statuses for jobs which are just queued since we're running inline anyway.
        if(is_dir($this->jobInfoDir) && is_writable($this->jobInfoDir) && $status['status'] != Job::STATUS_QUEUED) {
            file_put_contents("{$this->jobInfoDir}/$jobId", serialize($status));
        }
    }
}
