<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * CSNET - (Obsolete - used only for examples in some obsolete RFCs)
 * Formerly UNASSIGNED
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.2.4
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class CS extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    /**
     * @inheritdoc
     */
    protected string $name = 'CS';

    /**
     * @inheritdoc
     */
    protected int $value = Lookup::QCLASS_CS;
}
