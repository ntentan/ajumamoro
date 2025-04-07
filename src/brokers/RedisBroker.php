<?php
namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\Job;
use ajumamoro\JobInfo;
use Predis\Client;

/**
 * Redis messaging broker used for running AjumaMoro Jobs.
 */
class RedisBroker implements BrokerInterface
{
    /**
     * Instance of the redis client.
     */
    private ?Client $redis = null;

    private array $config;

    /**
     * RedisBroker constructor.
     */
    public function __construct(array $brokerConfig)
    {
        $this->config = $brokerConfig;
    }

    private function getRedis(): Client
    {
        if($this->redis === null) {
            $this->redis = new Client($this->config['parameters'] ?? null, $this->config['options'] ?? null);
        }
        return $this->redis;
    }

    /**
     * Get the next job on the job queue.
     */
    public function get(): JobInfo
    {
        do {
            $response = $this->getRedis()->rpop("job_queue");
            usleep(500);
        } while ($response === null);
        return unserialize($response);
    }

    /**
     * Add a job to the queue.
     * @param mixed $job
     */
    public function put(JobInfo $job): string
    {
        $redis = $this->getRedis();
        $job->id = $redis->incr("job_id_sequence");
        $redis->lpush("job_queue", serialize($job));
        return $job->id;
    }

    /**
     * Get the status of a job on the queue.
     */
    public function getStatus(string $jobId): array
    {
        return json_decode($this->getRedis()->get("job_status:$jobId"), true);
    }

    /**
     * Set the status of a job on the queue.
     */
    public function setStatus(string $jobId, array $status): void
    {
        $this->getRedis()->set("job_status:$jobId", json_encode($status));
    }
}
