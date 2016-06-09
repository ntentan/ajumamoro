<?php
namespace ajumamoro\brokers;

use ajumamoro\Broker;
use ntentan\config\Config;

class InlineBroker extends Broker
{
    public function get()
    {
        
    }

    public function init()
    {
        
    }

    public function put($job)
    {
        $job = unserialize($job['object']);
        $job->setup();
        $job->go();
        $job->tearDown();
    }
}
