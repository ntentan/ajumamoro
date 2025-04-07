<?php

namespace ajumamoro;

interface BrokerInterface
{
    public function put(JobInfo $job): string;
    public function get(): JobInfo;
    public function getStatus(string $jobId): array;
    public function setStatus(string $jobId, array $status): void;
}
