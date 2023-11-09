<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Cache\Adapter;

use ArrayAccess\DnsRecord\Cache\CacheData;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Cache\Psr6CacheAdapterInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class Psr6CacheAdapter implements Psr6CacheAdapterInterface
{
    private ?CacheItemPoolInterface $cacheItemPool;

    /**
     * @param ?CacheItemPoolInterface $cacheItemPool
     */
    public function __construct(
        ?CacheItemPoolInterface $cacheItemPool = null
    ) {
        if ($cacheItemPool) {
            $this->setCacheItemPool($cacheItemPool);
        }
    }

    /**
     * @inheritdoc
     */
    public function getCacheItemPool(): ?CacheItemPoolInterface
    {
        return $this->cacheItemPool;
    }

    /**
     * Set cache item
     * @param CacheItemPoolInterface $cacheItemPool
     * @return void
     */
    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool): void
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function saveItem(CacheDataInterface $cacheData): bool
    {
        $item = $this->getCacheItemPool()?->getItem($cacheData->getKey());
        if (!$item) {
            return false;
        }
        $item
            ->set($cacheData)
            ->expiresAfter($cacheData->getExpiresAfter());
        return $this->getCacheItemPool()->save($item);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function hasItem(string $key): bool
    {
        return $this->getCacheItemPool()?->hasItem($key)?:false;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {
        return $this->getCacheItemPool()?->deleteItem($key)?:false;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function deleteItems(string ...$keys): bool
    {
        return $this->getCacheItemPool()?->deleteItems($keys)?:false;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function getItem(string $key): CacheDataInterface
    {
        $cacheItem = $this->getCacheItem($key);
        $cacheItem = $cacheItem->get();
        if ($cacheItem instanceof CacheDataInterface) {
            return $cacheItem;
        }

        return new CacheData($key);
    }

    /**
     * @inheritdoc
     */
    public function clear(): bool
    {
        return $this->getCacheItemPool()?->clear()?:false;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function getCacheItem(string $key): ?CacheItemInterface
    {
        return $this->getCacheItemPool()?->getItem($key);
    }
}
