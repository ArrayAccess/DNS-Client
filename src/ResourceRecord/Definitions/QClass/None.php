<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * QCLASS None - RFC2136
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2136
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class None extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    /**
     * @inheritdoc
     */
    protected string $name = 'NONE';

    /**
     * @inheritdoc
     */
    protected int $value = Lookup::QCLASS_NONE;
}
