<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordClass;
use ArrayAccess\DnsRecord\Exceptions\InvalidArgumentException;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQClassInterface;
use function sprintf;

/**
 * Unassigned
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2929#section-3.2
 * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#table-dns-parameters-2
 */
class Unassigned extends AbstractResourceRecordClass implements ResourceRecordQClassInterface
{
    const RANGES = [
        [
            0x0005, // 5
            0x00FD  // 253
        ],
        [
            0x0100, // 256
            0xFEFF // 65279
        ]
    ];

    /**
     * @inheritdoc
     */
    protected string $name = 'UNASSIGNED';

    /**
     * Check whether unassigned
     *
     * @param int $value
     * @return bool
     */
    public static function inRange(int $value) : bool
    {
        foreach (self::RANGES as $ranges) {
            if ($value >= $ranges[0] && $value <= $ranges[0]) {
                return true;
            }
        }
        return false;
    }

    public function __construct(int $value)
    {
        if (!self::inRange($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    'QCLASS "%d" Invalid value for "UNASSIGNED"',
                    $value
                )
            );
        }
        $this->value = $value;
    }
}
