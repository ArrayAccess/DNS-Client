<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use function inet_ntop;

/**
 * A RDATA format - RFC1035 Section 3.4.1
 * ADDRESS A 32-bit Internet address.
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                    ADDRESS                    |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.4.1
 */
class A extends AbstractResourceRecordType
{
    const TYPE = 'A';

    protected string $address;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        $this->address = inet_ntop($this->rData)?:'';
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
     *     ip: string
     * }
     */
    public function toArray(): array
    {
        return [
            'host' => $this->getName(),
            'class' => $this->getClass()->getName(),
            'ttl' => $this->getTTL(),
            'type' => $this->getType()->getName(),
            'ip' => $this->getAddress(),
        ];
    }
}
