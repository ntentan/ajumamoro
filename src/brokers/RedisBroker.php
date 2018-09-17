<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\exceptions\BrokerConnectionException;
use Predis\Client;
use Predis\CommunicationException;

/**
 * Redis messaging broker used for running AjumaMoro Jobs
 *
 * @author ekow
 */
class RedisBroker implements BrokerInterface
{

    /**
     * Instance of the redis client
     * @var Client
     */
    private $redis;

    public function __construct($config)
    {
        $this->redis = new Client($config['parameters'], $config['options'] ?? null);
        try {
            $this->redis->connect();
        } catch (CommunicationException $ex) {
            throw new BrokerConnectionException(
                "Failed to connect to redis broker: {$ex->getMessage()}"
            );
        }
    }

    /**
     * Get the next job on the job queue.
     *
     * @return mixed Job
     */
    public function get()
    {
        do {
            $response = $this->redis->rpop("job_queue");
            usleep(500);
        } while ($response === null);
        return unserialize($response);
    }

    /**
     * Add a job to the queue.
     *
     * @param mixed $job
     * @return int
     */
    public function put($job)
    {
        $job['id'] = $this->redis->incr("job_id_sequence");
        $this->redis->lpush("job_queue", serialize($job));
        return $job['id'];
    }

    /**
     * Get the status of a job on the queue.
     *
     * @param $jobId
     * @return string
     */
    public function getStatus($jobId)
    {
        return json_decode($this->redis->get("job_status:$jobId"), true);
    }

    /**
     * Set the status of a job on the queue.
     *
     * @param $jobId
     * @param $status
     * @return mixed
     */
    public function setStatus($jobId, $status)
    {
        return $this->redis->set("job_status:$jobId", json_encode($status));
    }

}
