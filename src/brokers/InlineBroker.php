<?php

namespace ajumamoro\brokers;

use ajumamoro\BrokerInteface;

class InlineBroker implements BrokerInteface
{

    public function get() {
        
    }

    public function init() {
        
    }

    public function put($job) {
        $job = unserialize($job['object']);
        $job->setup();
        $job->go();
        $job->tearDown();
    }

}
