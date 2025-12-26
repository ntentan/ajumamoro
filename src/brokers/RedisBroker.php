<?php
namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\JobInfo;

/**
 * Redis messaging broker used for running AjumaMoro Jobs.
 */
class RedisBroker implements BrokerInterface
{
    /**
     * Instance of the redis client.
     */
    private redis\RedisClient $client;

    private array $config;

    /**
     * RedisBroker constructor.
     */
    public function __construct(redis\RedisClient $client, array $brokerConfig)
    {
        $this->config = $brokerConfig;
        $this->client = $client;
    }

    /**
     * Get the next job on the job queue.
     */
    public function get(): JobInfo
    {
        do {
            $response = $this->client->rpop("job_queue");
            usleep($this->config['redis.sleep_time'] ?? 500);
        } while ($response === null || $response === "");
        return unserialize($response);
    }

    /**
     * Add a job to the queue.
     * @param mixed $job
     */
    public function put(JobInfo $job): string
    {
        $job->id = $this->client->incr("job_id_sequence");
        $this->client->lpush("job_queue", serialize($job));
        return $job->id;
    }

    /**
     * Get the status of a job on the queue.
     */
    public function getStatus(string $jobId): array
    {
        return json_decode($this->client->get("job_status:$jobId"), true);
    }

    /**
     * Set the status of a job on the queue.
     */
    public function setStatus(string $jobId, array $status): void
    {
        $this->client->set("job_status:$jobId", json_encode($status));
    }
}
