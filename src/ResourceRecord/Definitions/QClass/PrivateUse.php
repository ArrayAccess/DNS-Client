<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Exceptions\InvalidArgumentException;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;

/**
 * Private Use
 * Range: 65280 - 65534/0xFF00 - 0xFFFE
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2929#section-3.2
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class PrivateUse extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    const RANGE_START = 0xFF00; // 65280

    const RANGE_END = 0xFFFE; // 65534

    /**
     * @inheritdoc
     */
    protected string $name = 'PRIVATE';

    public function __construct(int $value)
    {
        if ($value < self::RANGE_START || $value > self::RANGE_END) {
            throw new InvalidArgumentException(
                'Invalid value for QCLASS "PRIVATE"'
            );
        }

        $this->value = $value;
    }
}
