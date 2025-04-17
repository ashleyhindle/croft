<?php

declare(strict_types=1);

namespace Croft;

class Cache
{
    private array $cache = [];

    public function __construct(private string $prefix = 'croft') {}

    private function key(string $key): string
    {
        return $this->prefix.':'.$key;
    }

    public function has(string $key): bool
    {
        return isset($this->cache[$this->key($key)]);
    }

    public function get(string $key): mixed
    {
        return $this->cache[$this->key($key)] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->cache[$this->key($key)] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->cache[$this->key($key)]);
    }
}
