<?php

namespace ajumamoro\brokers;

use ajumamoro\Broker;
use ntentan\config\Config;
use ajumamoro\exceptions\BrokerConnectionException;

/**
 * Description of RedisBroker
 *
 * @author ekow
 */
class RedisBroker extends Broker
{
    /**
     *
     * @var \Predis\Client
     */
    private $redis;
    
    public function get()
    {
        do{
            $response = $this->redis->rpop("jobs");
            usleep(500);
        } 
        while ($response === null);
        return unserialize($response);
    }

    public function init()
    {
        $settings = Config::get('ajumamoro:broker');
        unset($settings['driver']);
        $this->redis = new \Predis\Client($settings);
        try{
            $this->redis->connect();            
        } catch (\Predis\CommunicationException $ex) {
            throw new BrokerConnectionException("Failed to connect to redis broker: {$ex->getMessage()}");
        }
    }

    public function put($job)
    {
        $job['id'] = $this->redis->incr("job_id");
        $this->redis->lpush("jobs", serialize($job));
        return $job['id'];
    }

}
