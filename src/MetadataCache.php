<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\ObjectRelationshipMapping;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionCache;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionSimpleCache;

class MetadataCache
{
    public function __construct(private readonly CacheItemPoolInterface|CacheInterface $cache)
    {
    }

    /**
     * @return array<Property>
     * @throws InvalidKeyException
     */
    public function get(string $class, callable $generator): array
    {
        $key = $this->getKey($class);
        try {
            if ($this->cache instanceof CacheInterface) {
                $item = $this->cache->get($key);
                if (!$item) {
                    $item = $generator();
                    $this->cache->set($key, $item);
                }
                return $item;
            }
            $item = $this->cache->getItem($key);
            if (!$item->isHit()) {
                $item->set($generator());
                $this->cache->save($item);
            }
            return $item->get();
        } catch (InvalidArgumentExceptionSimpleCache|InvalidArgumentExceptionCache $e) {
            throw new InvalidKeyException("Key {$class} => {$key} is invalid.", 0, $e);
        }
    }
    public function refresh(string $class, callable $generator): void
    {
        $key = $this->getKey($class);
        try {
            if ($this->cache instanceof CacheInterface) {
                $this->cache->set($key, $generator());
                return;
            }
            $item = $this->cache->getItem($key);
            $item->set($generator());
            $this->cache->save($item);
        } catch (InvalidArgumentExceptionSimpleCache|InvalidArgumentExceptionCache $e) {
            throw new InvalidKeyException("Key {$class} => {$key} is invalid.", 0, $e);
        }
    }
    private function getKey(string $class): string
    {
        return 'me.bjoern-buettner.metadata_' . str_replace('\\', '.', $class);
    }
}
