<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;

/**
 * CNAME RDATA Format - RFC1035 Section 3.3.1
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                     CNAME                     /
 *      /                                               /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.3.1
 */
class CNAME extends AbstractResourceRecordType
{
    const TYPE = 'CNAME';

    protected string $cname;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        $this->cname = Buffer::readLabel($message, $rdataOffset);
    }

    public function getCname(): string
    {
        return $this->cname;
    }
}
