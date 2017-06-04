<?php

namespace ajumamoro;

interface BrokerInterface
{
    
    public function put($job);

    public function get();
}
