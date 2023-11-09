<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;

/**
 * NS RDATA format - RFC1035 Section 3.3.11
 *
 * NSDNAME A <domain-name> which specifies a host which should be
 *  authoritative for the specified class and domain.
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                   NSDNAME                     /
 *      /                                               /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.3.11
 */
class NS extends AbstractResourceRecordType
{
    const TYPE = 'NS';

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        $this->value = Buffer::readLabel($message, $rdataOffset);
    }
}
