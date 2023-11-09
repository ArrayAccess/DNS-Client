<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * Assignment requires an IETF Standards Action.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2929#section-3.2
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class ReservedStart extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    /**
     * @inheritdoc
     */
    protected string $name = 'RESERVED';

    /**
     * @inheritdoc
     * @var int 0 / 0x0000
     */
    protected int $value = Lookup::QCLASS_RESERVED;
}
