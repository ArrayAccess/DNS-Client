<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Cache\Adapter;

use ArrayAccess\DnsRecord\Cache\CacheData;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheAdapterInterface;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheDataInterface;

class ArrayCacheAdapter implements CacheAdapterInterface
{
    /**
     * Store the cached data
     *
     * @var array<string, CacheDataInterface>
     */
    private array $data = [];

    /**
     * @inheritdoc
     */
    public function saveItem(CacheDataInterface $cacheData): bool
    {
        $this->data[$cacheData->getKey()] = $cacheData;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteItem(string $key): bool
    {
        unset($this->data[$key]);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteItems(string ...$keys): bool
    {
        foreach ($keys as $item) {
            unset($this->data[$item]);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getItem(string $key): CacheDataInterface
    {
        return $this->data[$key]??new CacheData($key);
    }

    /**
     * @inheritdoc
     */
    public function hasItem(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @inheritdoc
     */
    public function clear(): bool
    {
        $this->data = [];
        return true;
    }
}
