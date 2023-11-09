<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;

/**
 * HINFO RDATA format - RFC1035 Section 3.3.2
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                      CPU                      /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                       OS                      /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.3.2
 */
class HINFO extends AbstractResourceRecordType
{
    const TYPE = 'HINFO';

    protected string $cpu;
    protected string $os;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        $this->cpu = Buffer::readLabel(
            $message,
            $rdataOffset
        );
        $rdataOffset++;
        $this->os = Buffer::read(
            $message,
            $rdataOffset,
            $this->rdLength - strlen($this->cpu)
        );
    }
}
