<?php

namespace ajumamoro\brokers\redis;

class PhpRedisClient implements RedisClient
{
    private \Redis $client;

    public function __construct(\Redis $client)
    {
        $this->client = $client;
    }

    public function rpop(string $key): ?string
    {
        return $this->client->rPop($key);
    }

    public function lpush(string $key, string $value): int
    {
        return $this->client->lPush($key, $value);
    }

    public function incr(string $key): int
    {
        return $this->client->incr($key);
    }

    public function get(string $key): ?string
    {
        return $this->client->get($key);
    }

    public function set(string $key, string $value): int
    {
        return $this->client->set($key, $value);
    }
}
