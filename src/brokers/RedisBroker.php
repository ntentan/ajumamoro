<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;
use ajumamoro\exceptions\BrokerConnectionException;
use Predis\Client;
use Predis\CommunicationException;

/**
 * Description of RedisBroker
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
     *
     * @return Job
     */
    public function get()
    {
        do {
            $response = $this->redis->rpop("job_queue");
            usleep(500);
        } while ($response === null);
        return unserialize($response);
    }

    public function put($job)
    {
        $job['id'] = $this->redis->incr("job_id_sequence");
        $this->redis->lpush("job_queue", serialize($job));
        return $job['id'];
    }

    /**
     *
     * @param $jobId
     * @return string
     */
    public function getStatus($jobId)
    {
        return json_decode($this->redis->get("job_status:$jobId"), true);
    }

    public function setStatus($jobId, $status)
    {
        return $this->redis->set("job_status:$jobId", json_encode($status));
    }

}
