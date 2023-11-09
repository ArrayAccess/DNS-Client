<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Abstracts;

use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerInterface;
use function ltrim;
use function serialize;
use function strrchr;
use function unserialize;

abstract class AbstractDnsServer implements DnsServerInterface
{
    /**
     * Dns server name
     *
     * @var string
     */
    protected string $name;

    /**
     * Dns server object identity
     *
     * @var string
     */
    protected string $identity;

    /**
     * @var string primary DNS Server
     */
    protected string $primaryServer;

    /**
     * @var ?string secondary DNS server
     */
    protected ?string $secondaryServer = null;

    protected int $port = 53;

    /**
     * @inheritdoc
     */
    public function getPrimaryServer(): string
    {
        return $this->primaryServer;
    }

    /**
     * @inheritdoc
     */
    public function getSecondaryServer(): ?string
    {
        return $this->secondaryServer;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        // fallback default class name
        return $this->name ??= ltrim(strrchr($this::class, '\\'))?:$this::class;
    }

    /**
     * @inheritdoc
     */
    public function getIdentity(): string
    {
        // fallback default fqdn class name
        return $this->identity ??= $this::class;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @inheritdoc
     */
    public function serialize() : string
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $data) : void
    {
        $this->__unserialize(unserialize($data));
    }

    public function __serialize(): array
    {
        return [
            'identity' => $this->getIdentity(),
            'name' => $this->getName(),
            'primaryServer' => $this->getPrimaryServer(),
            'secondaryServer' => $this->getSecondaryServer(),
            'port' => $this->getPort()
        ];
    }

    /**
     * Magic method for unserialize
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->identity = $data['identity'];
        $this->name = $data['name'];
        $this->primaryServer = $data['primaryServer'];
        $this->secondaryServer = $data['secondaryServer'];
        $this->port = $data['port'];
    }
}
