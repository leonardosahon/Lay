<?php

namespace Lay\libs;

use Lay\core\Exception;
use Lay\core\LayConfig;
use Lay\core\traits\IsSingleton;

class LayCache
{
    use IsSingleton;

    private const default_path_to_cache = "cache";
    private string $cache_store;

    public function get_cache_path(): ?string
    {
        return $this->cache_store ?? null;
    }

    public function cache_exists(): bool
    {
        return isset($this->cache_store) && file_exists($this->cache_store);
    }

    public function store(string $key, mixed $value): bool
    {
        $cache = $this->read($key) ?? [];
        $cache[$key] = $value;

        $cache = json_encode($cache);

        if (!$cache)
            Exception::throw_exception("Could not store data in cache, please check your data", "MalformedCacheData");

        $cache = file_put_contents($this->cache_store, $cache);

        return !($cache === false);
    }

    public function read(string $key): mixed
    {
        if (!isset($this->cache_store))
            $this->cache_file(self::default_path_to_cache);

        if (!file_exists($this->cache_store))
            return null;

        $data = json_decode(file_get_contents($this->cache_store), true);

        if ($key === "*")
            return $data;

        $keys = explode(",", $key);

        if (count($keys) > 1) {
            $assoc = [];

            foreach ($keys as $k) {
                $assoc[$k] = $data[$k] ?? null;
            }

            return $assoc;
        }

        return $data[$key] ?? null;
    }

    public function cache_file(string $path_to_cache = "./", bool $use_lay_temp_dir = true, bool $invalidate = false): self
    {
        $server = LayConfig::res_server();

        $this->cache_store = $use_lay_temp_dir ? LayConfig::mk_tmp_dir() : $server->root;
        $this->cache_store = $this->cache_store . $path_to_cache;

        if($invalidate)
            file_put_contents($this->cache_store, "");

        return $this;
    }

    /**
     * @param mixed $data json encodable datatype
     * @return bool
     * @throws \Exception
     */
    public function dump(mixed $data): bool
    {
        if (!isset($this->cache_store))
            $this->cache_file(self::default_path_to_cache);

        $data = json_encode($data);

        if (!$data)
            Exception::throw_exception("Could not store data in cache, please check your data", "MalformedCacheData");

        $data = file_put_contents($this->cache_store, $data);

        return !($data === false);
    }

    public function export() : array
    {
        if (!isset($this->cache_store))
            $this->cache_file(self::default_path_to_cache);

        return json_decode(file_get_contents($this->cache_store), true);
    }
}