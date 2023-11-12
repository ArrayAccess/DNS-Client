<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\DnsServer;

use Countable;
use IteratorAggregate;
use Serializable;
use Traversable;

interface DnsServerStorageInterface extends IteratorAggregate, Countable, Serializable
{
    /**
     * Dns Server Storage
     *
     * @param DnsServerInterface $server dns server definition
     */
    public function __construct(DnsServerInterface $server);

    /**
     * Create default object
     * @see DnsServerStorage::DEFAULT_SERVER for default dns server
     *
     * @return static
     */
    public static function createDefault() : static;

    /**
     * Append dns server into collections
     *
     * @param DnsServerInterface $server
     * @phpstan-ignore-next-line
     */
    public function add(DnsServerInterface $server);

    /**
     * Remove dns server
     *
     * @param DnsServerInterface|string $server
     * @phpstan-ignore-next-line
     */
    public function remove(DnsServerInterface|string $server);

    /**
     * Get Dns server
     *
     * @param DnsServerInterface|string $server
     * @return ?DnsServerInterface
     */
    public function get(DnsServerInterface|string $server): ?DnsServerInterface;

    /**
     * Get lists of dns server
     *
     * @return array<string, DnsServerInterface>
     */
    public function getServers(): array;

    /**
     * @return Traversable<string, DnsServerInterface>
     */
    public function getIterator() : Traversable;
}
