<?php

namespace ajumamoro;

interface BrokerInterface
{
    public function put($job);
    public function get();
    public function getStatus($job);
    public function setStatus($job, $status);
}
