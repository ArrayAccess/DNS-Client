<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * Hesiod (HS) [Dyer 1987]
 * [Dyer, S., and F. Hsu, "Hesiod", Project Athena Technical Plan - Name Service, April 1987.]
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2929#section-3.2
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class HS extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    /**
     * @inheritdoc
     */
    protected string $name = 'HS';

    /**
     * @inheritdoc
     */
    protected int $value = Lookup::QCLASS_HS;
}
