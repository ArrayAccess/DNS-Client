<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use Serializable;

interface PacketAnswersInterface extends Serializable
{
    /**
     * Get resource if truncated or not
     * RR_TC
     *
     * @return bool
     */
    public function isTruncated() : bool;

    /**
     * Get raw message from dns server
     *
     * @return PacketMessageInterface
     */
    public function getMessage(): PacketMessageInterface;

    /**
     * Get header from response
     *
     * @return PacketHeaderInterface
     */
    public function getHeader(): PacketHeaderInterface;

    /**
     * Get a Question query from response
     * if QD is zero return null
     * Get first question
     *
     * @return ?PacketQuestionInterface
     */
    public function getQuestion(): ?PacketQuestionInterface;

    /**
     * @return array<PacketQuestionInterface>
     */
    public function getQuestions(): array;

    /**
     * Get resource records
     *
     * @return PacketResourceRecordsInterface
     */
    public function getRecords(): PacketResourceRecordsInterface;
}
