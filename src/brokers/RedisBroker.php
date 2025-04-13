<?php
namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\exceptions\BrokerException;
use ajumamoro\JobInfo;
use Predis\Client;
use Predis\Connection\ConnectionException;
use Predis\PredisException;

/**
 * Redis messaging broker used for running AjumaMoro Jobs.
 */
class RedisBroker implements BrokerInterface
{
    /**
     * Instance of the redis client.
     */
    private Client $client;

    private array $config;

    /**
     * RedisBroker constructor.
     */
    public function __construct(Client $client, array $brokerConfig)
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
            try {
                $response = $this->client->rpop("job_queue");
                usleep($this->config['redis.sleep_time'] ?? 500);
            } catch (PredisException $e) {
                throw new BrokerException($e->getMessage(), $e->getCode(), $e);
            }
        } while ($response === null);
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
