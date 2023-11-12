<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketHeaderInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketQuestionInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResourceRecordsInterface;
use function serialize;
use function unserialize;

class RequestDataWrapper implements PacketRequestDataInterface
{
    public function __construct(
        private readonly PacketRequestDataInterface $packetRequestData
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getHeader(): PacketHeaderInterface
    {
        return $this->packetRequestData->getHeader();
    }

    public function withHeader(PacketHeaderInterface $header): RequestDataWrapper
    {
        return new self($this->packetRequestData->withHeader($header));
    }

    /**
     * @inheritdoc
     */
    public function getQuestions(): array
    {
        return $this->packetRequestData->getQuestions();
    }

    public function getQuestion(): PacketQuestionInterface
    {
        return $this->packetRequestData->getQuestion();
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalRecords(): PacketResourceRecordsInterface
    {
        return clone $this->packetRequestData->getAdditionalRecords();
    }

    /**
     * @inheritdoc
     */
    public function getAuthorityRecords(): PacketResourceRecordsInterface
    {
        return clone $this->packetRequestData->getAuthorityRecords();
    }

    public function getAnswerRecords(): PacketResourceRecordsInterface
    {
        return clone $this->packetRequestData->getAnswerRecords();
    }

    /**
     * @inheritdoc
     */
    public function getDnsServerStorage(): DnsServerStorageInterface
    {
        return clone $this->packetRequestData->getDnsServerStorage();
    }

    /**
     * @return string
     */
    public function getQueryMessage() : string
    {
        return $this->packetRequestData->getQueryMessage();
    }

    /**
     * @inheritdoc
     */
    public function createRequest(?CacheStorageInterface $cacheStorage = null): PacketRequestInterface
    {
        return clone $this->packetRequestData->createRequest($cacheStorage);
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     * @param string $data
     * @return void
     */
    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Magic method serializing
     *
     * @return array{packetRequestData: PacketRequestDataInterface}
     */
    public function __serialize(): array
    {
        return ['packetRequestData' => $this->packetRequestData];
    }

    /**
     * Magic method unserialize
     *
     * @param array{packetRequestData: PacketRequestDataInterface} $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->packetRequestData = $data['packetRequestData'];
    }
}
