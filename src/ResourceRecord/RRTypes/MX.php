<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;

/**
 * MX RDATA format - RFC1035 Section 3.3.9
 *
 * PREFERENCE A 16-bit integer which specifies the preference given to
 * this RR among others at the same owner. Lower values
 * are preferred.
 *
 * EXCHANGE A <domain-name> which specifies a host willing to act as
 * a mail exchange for the owner name.
 *
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                  PREFERENCE                   |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      /                   EXCHANGE                    /
 *      /                                               /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * https://datatracker.ietf.org/doc/html/rfc1035#section-3.3.9
 */
class MX extends AbstractResourceRecordType
{
    const TYPE = 'MX';

    protected int $preference;

    protected string $exchange;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        ['preference' => $this->preference] = unpack(
            'npreference',
            Buffer::read($message, $rdataOffset, 2)
        );
        $this->exchange = Buffer::readLabel($message, $rdataOffset);
        $this->value = "$this->preference $this->exchange";
    }

    public function getPreference(): int
    {
        return $this->preference;
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return  [
            'host' => $this->getName(),
            'class' => $this->getClass()->getName(),
            'ttl' => $this->getTTL(),
            'type' => $this->getType()->getName(),
            'pri' => $this->getPreference(),
            'target' => $this->getExchange(),
        ];
    }
}
