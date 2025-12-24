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
//            try {
            $response = $this->client->rpop("job_queue");
            usleep($this->config['redis.sleep_time'] ?? 500);
//            } catch (PredisException $e) {
//                throw new BrokerException($e->getMessage(), $e->getCode(), $e);
//            }
        } while ($response === null);
        return unserialize($response);
    }

    /**
     * Add a job to the queue.
     * @param mixed $job
     */
    public function put(JobInfo $job): string
    {
//        try {
            $job->id = $this->client->incr("job_id_sequence");
            $this->client->lpush("job_queue", serialize($job));
            return $job->id;
//        } catch (ConnectionException $e) {
//            throw new BrokerException($e->getMessage(), $e->getCode(), $e);
//        }
    }

    /**
     * Get the status of a job on the queue.
     */
    public function getStatus(string $jobId): array
    {
//        try {
            return json_decode($this->client->get("job_status:$jobId"), true);
//        } catch (ConnectionException $e) {
//            throw new BrokerException($e->getMessage(), $e->getCode(), $e);
//        }
    }

    /**
     * Set the status of a job on the queue.
     */
    public function setStatus(string $jobId, array $status): void
    {
//        try {
            $this->client->set("job_status:$jobId", json_encode($status));
//        } catch (ConnectionException $e) {
//            throw new BrokerException($e->getMessage(), $e->getCode(), $e);
//        }

    }
}
