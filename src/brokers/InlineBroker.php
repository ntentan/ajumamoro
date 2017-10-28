<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;

class InlineBroker implements BrokerInterface
{
    private $status;

    public function get() {
        
    }

    public function put($job) {
        $job = unserialize($job['object']);
        $job->setup();
        $job->go();
        $job->tearDown();
    }

    public function getStatus($job)
    {
        return $this->status;
    }

    public function setStatus($job, $status)
    {
        $this->status = $status;
    }
}
