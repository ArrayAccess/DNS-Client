<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * QCLASS Any - RFC1035
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.2.5
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class Any extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    const NAME = 'ANY';

    /**
     * @inheritdoc
     */
    protected string $name = self::NAME;

    /**
     * @inheritdoc
     */
    protected int $value = Lookup::QCLASS_ANY;
}
