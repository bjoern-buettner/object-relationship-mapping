<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\ObjectRelationshipMapping;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class FileSystemCache implements CacheInterface
{
    private array $cache = [];
    public function __construct(private readonly string $cacheDirectory)
    {
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0755, true);
        }
    }
    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        if (is_file("{$this->cacheDirectory}/$key.cache.php")) {
            return $this->cache[$key] = include ("{$this->cacheDirectory}/$key.cache.php");
        }
        return $this->cache[$key] = $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->cache[$key] = $value;
        file_put_contents("{$this->cacheDirectory}/$key.cache.php", '<?php return ' . var_export($value, true) . ';');
        return true;
    }

    public function delete(string $key): bool
    {
        unlink("{$this->cacheDirectory}/$key.cache.php");
        unset($this->cache[$key]);
        return true;
    }

    public function clear(): bool
    {
       $this->cache = [];
       $success = true;
       foreach (scandir($this->cacheDirectory) as $file) {
           if (is_file("{$this->cacheDirectory}/$file") && str_ends_with($file, '.cache.php')) {
               $success = unlink("{$this->cacheDirectory}/$file") && $success;
           }
       }
       return $success;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success;
    }

    public function has(string $key): bool
    {
        return isset($this->cache[$key]) || is_file("{$this->cacheDirectory}/$key.cache.php");
    }
}