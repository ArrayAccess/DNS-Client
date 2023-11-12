<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use function inet_ntop;
use function substr;

/**
 * AAAA (IPv6) Data Format - RFC3596 Section 2.2
 *
 *       +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *       |                    ADDRESS                    |
 *       |                                               |
 *       |                    128 Bit                    |
 *       +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc3596#section-2.2
 */
class AAAA extends AbstractResourceRecordType
{
    const TYPE = 'AAAA';

    protected string $address;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        // 8 bit = 1 octets
        $this->address = inet_ntop(substr($this->rData, 0, 128 / 8)) ?: '';
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return array{
     *     host: string,
     *     class: string,
     *     ttl: int,
     *     type: string,
     *     ipv6: string
     * }
     */
    public function toArray(): array
    {
        return [
            'host' => $this->getName(),
            'class' => $this->getClass()->getName(),
            'ttl' => $this->getTTL(),
            'type' => $this->getType()->getName(),
            'ipv6' => $this->getAddress(),
        ];
    }
}
