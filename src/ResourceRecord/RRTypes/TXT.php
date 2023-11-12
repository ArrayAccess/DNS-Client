<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;

/**
 * TXT RData Format - RFC1035 Section 3.3.14
 *
 * TXT-DATA One or more <character-string>s.
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                   TXT-DATA                    /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @see AbstractResourceRecordType::parseRData() for logic
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.3.14
 */
class TXT extends AbstractResourceRecordType
{
    const TYPE = 'TXT';
}
