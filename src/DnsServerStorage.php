<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord;

use ArrayAccess\DnsRecord\DnsServer\Cloudflare;
use ArrayAccess\DnsRecord\DnsServer\ControlD;
use ArrayAccess\DnsRecord\DnsServer\Google;
use ArrayAccess\DnsRecord\DnsServer\OpenDNS;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerInterface;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerStorageInterface;
use ArrayIterator;
use Traversable;
use function is_string;
use function serialize;
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
        ControlD::class
    ];

    /**
     * @var array<string, DnsServerInterface>
     */
    protected array $servers = [];

    /**
     * @inheritdoc
     */
    public function __construct(DnsServerInterface $server)
    {
        $this->add($server);
    }

    /**
     * @inheritdoc
     */
    public static function createDefault() : static
    {
        $obj = null;
        foreach (self::DEFAULT_SERVER as $className) {
            if (!$obj) {
                $obj = new static(new $className);
                continue;
            }
            $obj->add(new $className);
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
