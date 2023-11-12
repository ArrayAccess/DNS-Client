<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use Countable;
use IteratorAggregate;
use Serializable;
use Traversable;

/**
 * Records Type collection
 *
 * @uses ResourceRecordTypeInterface
 */
interface PacketResourceRecordsInterface extends IteratorAggregate, Countable, Serializable
{
    /**
     * Add record
     *
     * @param ResourceRecordTypeInterface $record
     * @phpstan-ignore-next-line
     */
    public function add(ResourceRecordTypeInterface $record);

    /**
     * Remove record
     *
     * @param ResourceRecordTypeInterface $record
     * @phpstan-ignore-next-line
     */
    public function remove(ResourceRecordTypeInterface $record);

    /**
     * Get all stored records
     *
     * @return array<string, ResourceRecordTypeInterface>
     */
    public function getRecords(): array;

    /**
     * Get filtered resource by type
     *
     * @param string $type
     * @param bool $single
     * @return array<ResourceRecordTypeInterface>|ResourceRecordTypeInterface|null
     */
    public function getFilteredType(string $type, bool $single = false) : null|array|ResourceRecordTypeInterface;

    /**
     * Return array records
     *
     * @uses ResourceRecordTypeInterface::toArray()
     * @return array<array<ResourceRecordTypeInterface>>
     */
    public function toArray() : array;

    /**
     * @return Traversable<string, ResourceRecordTypeInterface>
     */
    public function getIterator(): Traversable;
}
