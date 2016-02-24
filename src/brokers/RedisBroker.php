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
            $job = $this->redis->rpop("jobs");
        }while($job === null);
        return unserialize($job);
    }

    public function init()
    {
        $settings = \ajumamoro\Config::get('broker');
        unset($settings['driver']);
        $this->redis = new \Predis\Client($settings);
    }

    public function put($job)
    {
        $this->redis->lpush("jobs", serialize($job));
        return $this->redis->incr("job_id");
    }

}
