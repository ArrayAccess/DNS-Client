<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * Internet (IN)
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2929#section-3.2
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class IN extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    const NAME = 'IN';

    /**
     * @inheritdoc
     */
    protected string $name = self::NAME;

    /**
     * @inheritdoc
     */
    protected int $value = Lookup::QCLASS_IN;
}
