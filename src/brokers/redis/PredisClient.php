<?php

namespace ajumamoro\brokers\redis;

readonly class PredisClient implements RedisClient
{

    private PredisClient $client;

    public function __construct(PredisClient $client)
    {
        $this->client = $client;
    }

    public function rpop(string $key): ?string
    {
        return $this->client->rpop($key);
    }

    public function lpush(string $key, string $value): int
    {
        return $this->client->lpush($key, $value);
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