<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;
use function sprintf;
use function unpack;

class SOA extends AbstractResourceRecordType
{
    const TYPE = 'SOA';

    private int $minimumTTL;

    private int $serial;

    private int $refresh;

    private int $expire;

    private int $retry;

    private string $mName;

    private string $rName;

    /**
     * @inheritdoc
     */
    protected function parseRData($message, int $rdataOffset): void
    {
        $this->mName = Buffer::readLabel($message, $rdataOffset);
        $this->rName = Buffer::readLabel($message, $rdataOffset);
        [
            'serial' => $this->serial,
            'refresh' => $this->refresh,
            'retry' => $this->retry,
            'expire' => $this->expire,
            'minTTL' => $this->minimumTTL
        ] = unpack(
            "Nserial/Nrefresh/Nretry/Nexpire/NminTTL",
            Buffer::read($message, $rdataOffset, 20)
        );

        $this->value = sprintf(
            '%s. %s. %d %d %d %d %d',
            $this->mName,
            $this->rName,
            $this->serial,
            $this->refresh,
            $this->retry,
            $this->expire,
            $this->minimumTTL
        );
    }

    public function getMinimumTTL(): int
    {
        return $this->minimumTTL;
    }

    public function getSerial(): int
    {
        return $this->serial;
    }

    public function getRefresh(): int
    {
        return $this->refresh;
    }

    public function getExpire(): int
    {
        return $this->expire;
    }

    public function getMName(): string
    {
        return $this->mName;
    }

    public function getRName(): string
    {
        return $this->rName;
    }

    public function getRetry(): int
    {
        return $this->retry;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'host' => $this->getName(),
            'class' => $this->getClass()->getName(),
            'ttl' => $this->getTTL(),
            'type' => $this->getType()->getName(),
            'mname' => $this->getMName(),
            'rname' => $this->getRName(),
            'serial' => $this->getSerial(),
            'refresh' => $this->getRefresh(),
            'retry' => $this->getRetry(),
            'expire' => $this->getExpire(),
            'minimum-ttl' => $this->getMinimumTTL(),
        ];
    }
}
