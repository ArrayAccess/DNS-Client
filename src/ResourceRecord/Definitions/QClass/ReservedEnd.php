<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * Can only be assigned by an IETF Standards Action.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2929#section-3.2
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class ReservedEnd extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    protected string $name = 'RESERVED';

    /**
     * @var int 65535 / 0xFFFF
     */
    protected int $value = Lookup::QCLASS_RESERVED_END;
}
