<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord;

use ArrayAccess\DnsRecord\DnsServer\Cloudflare;
use ArrayAccess\DnsRecord\DnsServer\CloudflareFamily;
use ArrayAccess\DnsRecord\DnsServer\ControlD;
use ArrayAccess\DnsRecord\DnsServer\Google;
use ArrayAccess\DnsRecord\DnsServer\OpenDNS;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerInterface;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerStorageInterface;
use ArrayIterator;
use Traversable;
use function count;
use function is_string;
use function serialize;
use function strtolower;
use function unserialize;

/**
 * Object to store dns servers definitions
 */
class DnsServerStorage implements DnsServerStorageInterface
{
    const DEFAULT_SERVER = [
        Google::class,
        Cloudflare::class,
        OpenDNS::class,
        ControlD::class,
        CloudflareFamily::class,
    ];

    /**
     * @var array<string, DnsServerInterface>
     */
    protected array $servers = [];

    /**
     * @var array<class-string, DnsServerInterface>
     */
    private static array $cachedDefaultServers = [];

    /**
     * @inheritdoc
     */
    public function __construct(DnsServerInterface $server)
    {
        $this->add($server);
    }

    /**
     * @param string $className
     * @return ?DnsServerInterface
     */
    public static function getDefaultServer(string $className) : ?DnsServerInterface
    {
        if (empty(self::$cachedDefaultServers)) {
            // create default
            foreach (self::DEFAULT_SERVER as $item) {
                self::$cachedDefaultServers[strtolower($item)] = $item;
            }
        }

        $clasName = ltrim(strtolower($className), '\\');
        if (!isset(self::$cachedDefaultServers[$clasName])) {
            return null;
        }
        if (is_string(self::$cachedDefaultServers[$clasName])) {
            self::$cachedDefaultServers[$clasName] = new self::$cachedDefaultServers[$clasName];
        }
        return self::$cachedDefaultServers[$clasName];
    }

    /**
     * @inheritdoc
     */
    public static function createDefault() : static
    {
        $obj = null;
        foreach (self::DEFAULT_SERVER as $className) {
            if (!$obj) {
                $obj = new static(self::getDefaultServer($className));
                continue;
            }
            $obj->add(self::getDefaultServer($className));
        }

        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function add(DnsServerInterface $server): void
    {
        $this->servers[$server->getIdentity()] = $server;
    }

    /**
     * @inheritdoc
     */
    public function remove(DnsServerInterface|string $server): void
    {
        $server = is_string($server) ? $server : $server->getIdentity();
        unset($this->servers[$server]);
    }

    /**
     * @inheritdoc
     */
    public function get(DnsServerInterface|string $server): ?DnsServerInterface
    {
        $dnsServer = is_string($server) ? $server : $server->getIdentity();
        return $this->servers[$dnsServer]??null;
    }

    /**
     * @inheritdoc
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * @inheritdoc
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->getServers());
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->getServers());
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Magic method serializing
     *
     * @return array{servers: Interfaces\DnsServer\DnsServerInterface[]}
     */
    public function __serialize(): array
    {
        return ['servers' => $this->servers];
    }

    /**
     * Magic method unserialize
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->servers = $data['servers'];
    }
}
