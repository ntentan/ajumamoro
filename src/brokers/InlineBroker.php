<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInterface;

/**
 * The inline broker runs inline within the current request or session.
 * This broker should only be used for test cases or in CLI applications.
 */
class InlineBroker implements BrokerInterface
{
    private $status;

    public function get()
    {
    }

    public function put($job)
    {
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
