<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;

/**
 * Chaos (CH) [Moon 1981]
 * [D. Moon, "Chaosnet", A.I. Memo 628,
 *  Massachusetts Institute of Technology Artificial Intelligence Laboratory, June 1981.]
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2929#section-3.2
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class CH extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    /**
     * @inheritdoc
     */
    protected string $name = 'CH';

    /**
     * @inheritdoc
     */
    protected int $value = Lookup::QCLASS_CH;
}
