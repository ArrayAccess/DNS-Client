<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerStorageInterface;
use Serializable;

interface PacketRequestDataInterface extends Serializable
{
    /**
     * Get packet header from given constructor argument
     *
     * @return PacketHeaderInterface
     */
    public function getHeader() : PacketHeaderInterface;

    /**
     * @param PacketHeaderInterface $header
     * @return $this
     */
    public function withHeader(PacketHeaderInterface $header) : PacketRequestDataInterface;

    /**
     * Get packet questions from given constructor argument
     *
     * @return array<PacketQuestionInterface>
     */
    public function getQuestions() : array;

    /**
     * Get the first Question
     *
     * @return PacketQuestionInterface
     */
    public function getQuestion() : PacketQuestionInterface;

    /**
     * Get additional resource records
     *
     * @return PacketResourceRecordsInterface
     */
    public function getAdditionalRecords(): PacketResourceRecordsInterface;

    /**
     * @return PacketResourceRecordsInterface
     */
    public function getAuthorityRecords(): PacketResourceRecordsInterface;

    /**
     * Get of answer records
     *
     * @return PacketResourceRecordsInterface
     */
    public function getAnswerRecords(): PacketResourceRecordsInterface;

    /**
     * Get Dns server storage
     *
     * @return DnsServerStorageInterface
     */
    public function getDnsServerStorage() : DnsServerStorageInterface;

    /**
     * Get Query raw message to send to dns server
     *
     * @return string
     */
    public function getQueryMessage() : string;

    /**
     * Create new Request
     *
     * @param ?CacheStorageInterface $cacheStorage to make request support cache
     * @return PacketRequestInterface
     */
    public function createRequest(
        ?CacheStorageInterface $cacheStorage = null
    ) : PacketRequestInterface;
}
