<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;

/**
 * PTR Data Format - RFC1035 Section 3.5
 *
 * PTRDNAME A <domain-name> which points to some location in the domain name space.
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                   PTRDNAME                    /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.5
 */
class PTR extends AbstractResourceRecordType
{
    const TYPE = 'PTR';

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        // read domain name space
        $this->value = Buffer::readLabel($message, $rdataOffset);
    }
}
