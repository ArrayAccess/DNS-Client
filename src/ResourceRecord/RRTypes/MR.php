<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;

/**
 * MR RDATA format (EXPERIMENTAL) - RFC1035 Section 3.3.8
 * NEWNAME A <domain-name> which specifies a mailbox which is the
 * proper renamed of the specified mailbox.
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                   NEWNAME                    /
 *      /                                              /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.3.8
 */
class MR extends AbstractResourceRecordType
{
    const TYPE = 'MR';

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        // read domain name space
        $this->value = Buffer::readLabel($message, $rdataOffset);
    }
}
// @todo add toArray()
