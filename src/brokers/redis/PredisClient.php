<?php

namespace ajumamoro\brokers\redis;

use Predis\Client;

class PredisClient implements RedisClient
{

    private Client $client;

    public function __construct(Client $client)
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