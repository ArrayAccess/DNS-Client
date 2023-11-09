<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResponseInterface;
use ArrayAccess\DnsRecord\Traits\PacketSenderTrait;
use ArrayAccess\DnsRecord\Utils\Lookup;
use function strlen;

class Request implements PacketRequestInterface
{
    use PacketSenderTrait;

    /**
     * @var PacketResponseInterface
     */
    private PacketResponseInterface $response;

    /**
     * @var ?CacheStorageInterface
     */
    private ?CacheStorageInterface $cache = null;

    public function __construct(private readonly PacketRequestDataInterface $packetData)
    {
    }

    public function getCache(): ?CacheStorageInterface
    {
        return $this->cache;
    }

    public function setCache(?CacheStorageInterface $cache): static
    {
        $this->cache = $cache;
        return $this;
    }

    public function getPacketData(): PacketRequestDataInterface
    {
        return $this->packetData;
    }

    /**
     * @inheritdoc
     */
    public function getResponse() : PacketResponseInterface
    {
        return $this->response ??= $this->send();
    }

    /**
     * @inheritdoc
     */
    public function send(int|float $timeout = self::DEFAULT_TIMEOUT) : PacketResponseInterface
    {
        $query = $this->getPacketData()->getQueryMessage();
        // $type = $this->getPacketData()->getQuestion()->getType();
        $queryLength = strlen($query);
        // ixfr & axfr is tcp
        $isUdp = $queryLength < Lookup::MAX_UDP_SIZE;// && $type !== 'AXFR' && $type !== 'IXFR';
        $packetData = $this->getPacketData();
        /** @noinspection PhpUnhandledExceptionInspection */
        $response = $isUdp ? $this->sendUDP($packetData, $timeout) : $this->sendTCP($packetData, $timeout);
        $this->saveResponseCache($response);
        $this->response ??= $response;
        return $response;
    }
}
