<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResourceRecordsInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use ArrayIterator;
use Traversable;
use function md5;
use function serialize;
use function strtoupper;
use function unserialize;

/**
 * Records storage to store RR Object data
 */
class Records implements PacketResourceRecordsInterface
{
    /**
     * @var array<ResourceRecordTypeInterface>
     */
    private array $records = [];

    /**
     * Generate key to store in object records
     *
     * @param ResourceRecordTypeInterface $record
     * @return string
     */
    private function generateKey(ResourceRecordTypeInterface $record): string
    {
        // using md5
        return md5(
            $record->getRData()
            . $record->getHeader()
            . $record->getType()->getName()
            . $record->getName()
        );
    }

    /**
     * @inheritdoc
     *
     * @return void
     */
    public function add(ResourceRecordTypeInterface $record): void
    {
        $this->records[$this->generateKey($record)] = $record;
    }

    /**
     * @inheritdoc
     *
     * @return void
     */
    public function remove(ResourceRecordTypeInterface $record): void
    {
        unset($this->records[$this->generateKey($record)]);
    }

    /**
     * @inheritdoc
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @inheritdoc
     */
    public function getFilteredType(string $type, bool $single = false) : null|array|ResourceRecordTypeInterface
    {
        $type = strtoupper(trim($type));
        $result = [];
        foreach ($this->getRecords() as $record) {
            if ($record->getType()->getName() === $type) {
                if ($single) {
                    return $record;
                }
                $result[] = $record;
            }
        }

        return $result === [] ? null : $result;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getRecords());
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->records);
    }

    public function serialize() : string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Magic method for serializing
     *
     * @return array{records: ResourceRecordTypeInterface[]}
     */
    public function __serialize(): array
    {
        return [
            'records' => $this->records
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
        $this->records = $data['records'];
    }
}
