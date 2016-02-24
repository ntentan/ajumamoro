<?php

namespace ajumamoro\brokers;

use ajumamoro\Broker;

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
        $settings = \ajumamoro\Config::get('broker');
        unset($settings['driver']);
        $this->redis = new \Predis\Client($settings);
    }

    public function put($job)
    {
        $job['id'] = $this->redis->incr("job_id");
        $this->redis->lpush("jobs", serialize($job));
        return $job['id'];
    }

}
