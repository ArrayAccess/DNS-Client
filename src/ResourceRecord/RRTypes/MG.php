<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;

/**
 * MG RDATA format (EXPERIMENTAL) - RFC1035 Section 3.3.6
 * MGMNAME A <domain-name> which specifies a mailbox which is a
 *  member of the mail group specified by the domain name
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                   MGMNAME                    /
 *      /                                              /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.3.6
 */
class MG extends AbstractResourceRecordType
{
    const TYPE = 'MG';

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
