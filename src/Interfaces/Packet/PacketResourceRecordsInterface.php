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
     */
    public function add(ResourceRecordTypeInterface $record);

    /**
     * Remove record
     *
     * @param ResourceRecordTypeInterface $record
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
     * @return array|ResourceRecordTypeInterface|null
     */
    public function getFilteredType(string $type, bool $single = false) : null|array|ResourceRecordTypeInterface;

    /**
     * Return array records
     * @uses ResourceRecordTypeInterface::toArray()
     * @return array<array>
     */
    public function toArray() : array;

    /**
     * @return Traversable<ResourceRecordTypeInterface>
     */
    public function getIterator(): Traversable;
}
