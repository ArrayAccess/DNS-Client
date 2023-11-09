<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Cache\Adapter;

use ArrayAccess\DnsRecord\Cache\CacheData;
use ArrayAccess\DnsRecord\Exceptions\CacheException;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheAdapterInterface;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheDataInterface;
use BadMethodCallException;
use Exception;
use Generator;
use InvalidArgumentException;
use Throwable;
use function array_shift;
use function base64_encode;
use function count;
use function error_clear_last;
use function fclose;
use function file_exists;
use function filemtime;
use function fread;
use function func_get_args;
use function function_exists;
use function hash;
use function in_array;
use function ini_get;
use function is_array;
use function is_dir;
use function is_file;
use function is_int;
use function is_string;
use function md5;
use function preg_match;
use function preg_replace;
use function random_bytes;
use function realpath;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function str_replace;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function time;
use function trim;
use function unlink;
use function unserialize;
use function var_export;
use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_BOOL;
use const PHP_INT_MAX;
use const PHP_SAPI;

class FileCacheAdapter implements CacheAdapterInterface
{
    private string $namespace;

    private string $directory;

    /**
     * @var array<CacheDataInterface>
     */
    private array $values = [];

    private array $files = [];

    private string $prefixTemp = 'tmp-';

    private static array $cachesValues = [];

    /**
     * @throws CacheException
     */
    public function __construct(
        string $namespace = '',
        string $directory = null,
        protected ?int $defaultLifetime = null,
    ) {
        $this->doInit($namespace, $directory);
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @throws CacheException
     */
    private function doInit(string $namespace, ?string $directory): void
    {
        $namespace = trim($namespace);
        $namespace = preg_replace('~[^a-z0-9_\-.]~i', '', $namespace);
        if ($namespace === '') {
            $namespace = '@';
        }
        if (!$directory
            || trim($directory) === ''
            || trim(trim($directory), '/\\') === ''
        ) {
            $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dns-client-caches';
        }
        $directory = realpath($directory)?:rtrim($directory, '/\\');
        $directory .= DIRECTORY_SEPARATOR;
        $this->namespace = $namespace;
        $this->directory = $directory . $namespace . DIRECTORY_SEPARATOR;
        // On Windows the whole path is limited to 258 chars
        if ('\\' === DIRECTORY_SEPARATOR && strlen($directory) > 234) {
            throw new InvalidArgumentException(
                sprintf('Cache directory too long (%s).', $directory)
            );
        }
        if (!is_dir($this->directory)) {
            $this->doCallbackReduceError(
                'mkdir',
                $this->directory,
                0777,
                true
            );
        }
        if (!is_dir($this->directory)) {
            throw new CacheException(
                'Cache directory does not exists'
            );
        }
    }

    private function doCallbackReduceError(callable $callback, ...$arguments)
    {
        set_error_handler(static fn () => error_clear_last());
        try {
            $result = $callback(...$arguments);
        } finally {
            restore_error_handler();
        }

        return $result;
    }

    /**
     * @return bool
     */
    private static function isOpcacheSupport(): bool
    {
        static $enabled = null;
        return $enabled ??= function_exists('opcache_invalidate')
            &&
            filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL)
            && (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)
                || filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOL)
            );
    }

    private function scanFiles(string $directory, bool $loopDir = false) : Generator
    {
        if (!is_dir($directory)) {
            return '';
        }
        $directory = rtrim(realpath($directory)??$directory, '\\/');
        $chars = '+-ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = strlen($chars);
        for ($i = 0; $i < $length; ++$i) {
            $baseDir = $directory.DIRECTORY_SEPARATOR.$chars[$i];
            if (!is_dir($baseDir)) {
                continue;
            }
            for ($j = 0; $j < $length; ++$j) {
                if (!is_dir($dir = $baseDir.DIRECTORY_SEPARATOR.$chars[$j])) {
                    continue;
                }
                foreach ($this
                             ->doCallbackReduceError(
                                 'scandir',
                                 $dir,
                                 SCANDIR_SORT_NONE
                             ) ?: [] as $file) {
                    if ('.' !== $file && '..' !== $file) {
                        yield $dir => $dir.DIRECTORY_SEPARATOR.$file;
                    }
                }
                if ($loopDir) {
                    yield $dir => $dir;
                }
            }
            if ($loopDir) {
                yield $baseDir => $baseDir;
            }
        }
    }

    public function prune(): bool
    {
        $time = time();
        $pruned = false;
        $c = 0;
        foreach ($this->scanFiles($this->directory) as $file) {
            if (!is_file($file)) {
                continue;
            }
            $value = null;
            $valid = $this->validatePartialFile($file);
            try {
                if ($valid
                    && is_array(($expiresAt = include $file))
                    && count($expiresAt) === 3
                    && is_int($expiresAt[0]??null)
                    && is_string($expiresAt[1]??null)
                    && ($value = ($expiresAt[2]??null))
                    && $value instanceof CacheDataInterface
                    && $expiresAt[1] === md5($value->getKey())
                ) {
                    $expiresAt = $expiresAt[0];
                } else {
                    $expiresAt = $time;
                }
            } catch (Throwable) {
                $expiresAt = $time;
            }
            if ($time < $expiresAt && isset($value)) {
                if ((++$c % 10) === 0) {
                    $c = 1;
                    $this->clearLocalCache();
                }

                $this->values[$value->getKey()] = $value;
                self::$cachesValues[$file] = $expiresAt;
                continue;
            }
            $this->doUnlink($file);
        }

        return $pruned;
    }

    private function clearLocalCache(): void
    {
        $maximum = 150;
        // reduce to 125
        $max = 125;
        if (count($this->values) > $maximum) {
            while (count($this->values) > $max) {
                array_shift($this->values);
            }
        }
        if (count(self::$cachesValues) > $maximum) {
            while (count(self::$cachesValues) > $max) {
                array_shift(self::$cachesValues);
            }
        }
        if (count($this->files) > $maximum) {
            while (count($this->files) > $max) {
                array_shift(self::$cachesValues);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function doSave(CacheDataInterface $cacheData): bool
    {
        $lifetime = $cacheData->getExpiresAfter();
        $expiry = $lifetime ? time() + $lifetime : PHP_INT_MAX;
        $expiryString = $lifetime === PHP_INT_MAX ? 'PHP_INT_MAX' : $expiry;
        $allowCompile = self::isOpcacheSupport();

        $key = $cacheData->getKey();
        $file = $this->files[$key] = $this->getFile($key, true);
        $this->values[$key] = $cacheData;

        self::$cachesValues[$file] = $expiry;
        $value   = var_export(serialize($cacheData), true);
        $hashKey = md5($key);
        $value = "return [$expiryString, '$hashKey', $value];";
        $ok = $this->write($file, "<?php $value\n");

        $this->clearLocalCache();
        if ($allowCompile) {
            $this->doCallbackReduceError('opcache_invalidate', $file, true);
            $this->doCallbackReduceError('opcache_compile_file', $file);
        }

        return $ok;
    }

    /**
     * @throws Exception
     */
    private function generateTempFile(): string
    {
        $base = $this->directory . $this->prefixTemp;
        $basicLength = 258 - strlen($base);
        $basicLength = min($basicLength, 22);
        $count = 15;
        do {
            $file = str_replace(
                '/',
                '-',
                hash('xxh128', random_bytes(8))
            );
            $file = $base . substr($file, 0, $basicLength);
        } while (file_exists($file) && --$count > 0);
        return $file;
    }

    /**
     * @throws Exception
     */
    private function write(string $file, string $data) : bool
    {
        $unlink = false;
        try {
            set_error_handler(static function ($errCode, $errorMessage) {
                throw new Exception($errorMessage, $errCode);
            });
            $tmp = $this->generateTempFile();
            try {
                $h = fopen($tmp, 'x');
            } catch (Throwable $e) {
                if (!str_contains($e->getMessage(), 'File exists')) {
                    throw $e;
                }
                $tmp = $this->generateTempFile();
                $h = fopen($tmp, 'x');
            }

            fwrite($h, $data);
            fclose($h);
            $unlink = true;
            // 3 days & set to clear the cache
            touch($tmp, time() + 259200);
            // remove first
            if (file_exists($file)) {
                $this->doUnlink($file);
            }
            $success = $this->doCallbackReduceError('rename', $tmp, $file);
            $unlink = !$success;
        } finally {
            restore_error_handler();
            if ($unlink && isset($tmp)) {
                $this->doUnlink($tmp);
            }
        }
        return $success;
    }

    private function getFile(
        string $id,
        bool $mkdir = false
    ): string {
        // Use xxh128 to favor speed over security, which is not an issue here
        $hash = str_replace('/', '-', base64_encode(hash('xxh128', $this::class.$id, true)));
        $dir = $this->directory.strtoupper(
            $hash[0]
            . DIRECTORY_SEPARATOR
            . $hash[1]
            . DIRECTORY_SEPARATOR
        );
        if ($mkdir && !is_dir($dir)) {
            $this->doCallbackReduceError('mkdir', $dir, 0777, true);
        }

        return $dir . substr($hash, 2, 20);
    }

    private function doIdDelete(string ...$ids) : bool
    {
        $succeed = true;
        foreach ($ids as $id) {
            unset($this->values[$id]);
            $file = ($this->files[$id]??= $this->getFile($id));
            if (is_file($file)) {
                self::$cachesValues[$file] = 0;
                $succeed = $this->doUnlink($file) && $succeed;
            }
        }

        return $succeed;
    }

    private function doDelete(string $id, string ...$ids) : bool
    {
        $arrayId = [];
        foreach (func_get_args() as $item) {
            $arrayId[] = $item;
        }
        if (empty($arrayId)) {
            return true;
        }
        return $this->doIdDelete(...$arrayId);
    }

    private function doUnlink(string $file)
    {
        if (self::isOpcacheSupport()) {
            $this->doCallbackReduceError('opcache_invalidate', $file, true);
        }
        self::$cachesValues[$file] = 0;
        return $this->doCallbackReduceError(fn () => is_file($file) && unlink($file));
    }

    /**
     * @param string $file
     * @return bool
     */
    private function validatePartialFile(string $file) : bool
    {
        if (!file_exists($file)) {
            return false;
        }
        $socket = $this->doCallbackReduceError('fopen', $file, 'r');
        if (!$socket) {
            return false;
        }
        //  <?php return [1699348898,
        $data = fread($socket, 100);
        fclose($socket);
        if (!$data) {
            return false;
        }

        return (bool) preg_match('~^<\?php\s+return\s+\[[0-9]+,~', $data);
    }

    /**
     * @param string $key
     * @return CacheDataInterface|null
     */
    private function doFetch(string $key): ?CacheDataInterface
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }

        $file = ($this->files[$key]??= $this->getFile($key));
        if (isset(self::$cachesValues[$file])
            || !file_exists($file)
        ) {
            return null;
        }
        $valid = $this->validatePartialFile($file);
        $unlink = true;
        $time = time();
        try {
            set_error_handler(static function ($errCode, $errMessage) {
                throw new Exception($errMessage, $errCode);
            });
            if ($valid
                && is_array(($expiresAt = include $file))
                && count($expiresAt) === 3
                && is_int($expiresAt[0] ?? null)
                && is_string($expiresAt[1] ?? null)
                && is_string(($expiresAt[2]??null))
                && ($value = unserialize($expiresAt[2]))
                && $value instanceof CacheDataInterface
                && $expiresAt[1] === md5($value->getKey())
            ) {
                $expiresAt = $expiresAt[0];
            } else {
                $expiresAt = $time;
            }
            if ($time < $expiresAt && isset($value)) {
                $unlink = false;
                $this->clearLocalCache();
                self::$cachesValues[$file] = $time - $expiresAt;
                return $this->values[$key] = new CacheData(
                    $value->getKey(),
                    $value->get()
                );
            }
        } catch (Throwable) {
            $unlink = true;
            return null;
        } finally {
            if ($unlink && file_exists($file)) {
                self::$cachesValues[$file] = 0;
                unset($this->values[$key]);
                $this->doUnlink($file);
            }
        }

        return null;
    }

    public function __sleep(): array
    {
        throw new BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup()
    {
        throw new BadMethodCallException('Cannot unserialize '.__CLASS__);
    }

    /**
     * @throws Exception
     */
    public function saveItem(CacheDataInterface $cacheData): bool
    {
        return $this->doSave($cacheData);
    }

    public function hasItem(string $key): bool
    {
        if (isset($this->values[$key])) {
            return true;
        }
        return $this->doFetch($key) !== null;
    }

    public function deleteItem(string $key): bool
    {
        return $this->doDelete($key);
    }

    public function deleteItems(string ...$keys): bool
    {
        return $this->doDelete(...$keys);
    }

    public function getItem(string $key): CacheDataInterface
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }
        return $this->doFetch($key)??(new CacheData($key))->expiresAt(
            $this->defaultLifetime === 0
                ? null
                : $this->defaultLifetime
        );
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->clearLocalCache();
        foreach ($this->scanFiles($this->directory, true) as $dir => $file) {
            if ($dir !== $file) {
                $this->doUnlink($file);
                continue;
            }
            // do remove directory
            $this->doCallbackReduceError('rmdir', $dir);
        }

        $time = time();
        // remove temporary
        foreach ($this->doCallbackReduceError('scandir', $this->directory)?:[] as $path) {
            if ($path === '.' || $path === '..') {
                continue;
            }
            if (!str_starts_with($path, $this->prefixTemp)
                // should be less than cached
                || filemtime($this->directory . $path) > $time
            ) {
                continue;
            }
            $this->doUnlink($this->directory . $path);
        }

        return true;
    }
}
