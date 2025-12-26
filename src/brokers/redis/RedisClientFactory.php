<?php

namespace ajumamoro\brokers\redis;

class RedisClientFactory
{
    private $config;
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createClient(): RedisClient
    {
        $driver = $this->config['redis.driver'] ?? 'predis';

        return match ($driver) {
            'phpredis' => (function () {
                $redis = new \Redis();
                $redis->connect($this->config['redis.host'], $this->config['redis.port'] ?? 6379);
                if (isset($this->config['redis.password'])) {
                    $redis->auth($this->config['redis.password']);
                }
                return new PhpRedisClient($redis);
            })(),

            'predis' => (function () {
                $client = new \Predis\Client($this->config['redis.host'], ['exceptions' => true]);
                return new PredisClient($client);
            })(),

            default => throw new \RuntimeException("Unsupported redis driver: $driver"),
        };
    }
}
