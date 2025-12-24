<?php

namespace ajumamoro\brokers\redis;

interface RedisClient
{
    public function rpop(string $key): ?string;
    public function lpush(string $key, string $value): int;
    public function incr(string $key): int;
    public function get(string $key): ?string;
    public function set(string $key, string $value): int;
}