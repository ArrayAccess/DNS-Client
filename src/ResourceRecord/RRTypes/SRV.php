<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;
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

    protected string $target;

    /**
     * @inheritdoc
     */
    protected function parseRData($message, int $rdataOffset): void
    {
        $offset = 0;
        [
            'priority' => $this->priority,
            'weight' => $this->weight,
            'port' => $this->port,
        ] = unpack(
            'npriority/nweight/nport',
            Buffer::read($this->rData, $offset, 6)
        );

        $this->target = Buffer::readLabel($this->rData, $offset);
    }
}
