<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;
use function is_array;
use function unpack;

/**
 * SRV Resource Record - RFC2782
 * Standard using RFC6335
 */
class SRV extends AbstractResourceRecordType
{
    const TYPE = 'SRV';

    protected int $priority;

    protected int $weight;

    protected int $port;

    /**
     * @inheritdoc
     */
    protected function parseRData($message, int $rdataOffset): void
    {
        $offset = 0;
        $data = unpack(
            'npriority/nweight/nport',
            Buffer::read($this->rData, $offset, 6)
        );
        if (is_array($data)) {
            [
                'priority' => $this->priority,
                'weight' => $this->weight,
                'port' => $this->port,
            ] = $data;
        }

        $this->value = Buffer::readLabel($this->rData, $offset);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return array{
     *     host: string,
     *     class: string,
     *     type: string,
     *     pri: int,
     *     weight: int,
     *     port: int,
     *     target: ?string,
     * }
     */
    public function toArray(): array
    {
        return [
            'host' => $this->getName(),
            'class' => $this->getClass()->getName(),
            'ttl' => $this->getTTL(),
            'type' => $this->getType()->getName(),
            'pri' => $this->getPriority(),
            'weight' => $this->getWeight(),
            'port' => $this->getPort(),
            'target' => $this->getValue(),
        ];
    }
}
