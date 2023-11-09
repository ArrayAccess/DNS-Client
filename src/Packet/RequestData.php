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

class RequestData implements PacketRequestDataInterface
{

    protected array $questions;

    protected PacketResourceRecordsInterface $additionalRecords;

    protected PacketResourceRecordsInterface $authorityRecords;

    protected PacketResourceRecordsInterface $answerRecords;

    /**
     * @param PacketHeaderInterface $header
     * @param DnsServerStorageInterface $dnsServerStorage
     * @param PacketQuestionInterface $question
     * @param PacketQuestionInterface ...$questions
     */
    public function __construct(
        protected PacketHeaderInterface $header,
        protected DnsServerStorageInterface $dnsServerStorage,
        PacketQuestionInterface $question,
        PacketQuestionInterface ...$questions
    ) {
        $this->questions = [$question];
        foreach ($questions as $question) {
            $this->questions[] = $question;
        }
    }

    /**
     * @inheritdoc
     */
    public function getHeader(): PacketHeaderInterface
    {
        return $this->header;
    }

    /**
     * @inheritdoc
     */
    public function withHeader(PacketHeaderInterface $header): static
    {
        $obj = clone $this;
        $obj->header = $header;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * @inheritdoc
     */
    public function getQuestion(): PacketQuestionInterface
    {
        return reset($this->questions);
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalRecords(): PacketResourceRecordsInterface
    {
        return $this->additionalRecords ??= new Records();
    }

    /**
     * @inheritdoc
     */
    public function getAuthorityRecords(): PacketResourceRecordsInterface
    {
        return $this->authorityRecords ??= new Records();
    }

    /**
     * @inheritdoc
     */
    public function getAnswerRecords(): PacketResourceRecordsInterface
    {
        return $this->answerRecords ??= new Records();
    }

    /**
     * @inheritdoc
     */
    public function getDnsServerStorage(): DnsServerStorageInterface
    {
        return $this->dnsServerStorage;
    }

    /**
     * @return string
     */
    public function getQueryMessage() : string
    {
        $query  = '';
        $qdCount = 0;
        $anCount = 0;
        $arCount = 0;
        $aaCount = 0;
        foreach ($this->getQuestions() as $question) {
            $qdCount++;
            $query .= $question->getMessage();
        }

        // get answer records
        foreach ($this->getAnswerRecords()->getRecords() as $record) {
            $anCount++;
            $qdCount++;
            $query .= $record->getHeader();
            $query .= $record->getQueryMessage();
            $query .= "\0";
        }

        // get additional records
        foreach ($this->getAuthorityRecords()->getRecords() as $record) {
            $aaCount++;
            $qdCount++;
            $query .= $record->getHeader();
            $query .= $record->getQueryMessage();
            $query .= "\0";
        }
        // get additional
        foreach ($this->getAdditionalRecords()->getRecords() as $record) {
            $arCount++;
            $qdCount++;
            $query .= $record->getHeader();
            $query .= $record->getQueryMessage();
            $query .= "\0";
        }

        $this->header = $this->getHeader()
            ->withARCount($arCount)
            ->withQDCount($qdCount)
            ->withANCount($anCount)
            ->withAAFlag($aaCount > 0);
        return $this
                ->header
                ->getMessage()
                . $query;
    }

    /**
     * @inheritdoc
     */
    public function createRequest(
        ?CacheStorageInterface $cacheStorage = null
    ): PacketRequestInterface {
        return (new Request($this))->setCache($cacheStorage);
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function serialize() : string
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
     * Magic method for serializing
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'questions' => $this->getQuestions(),
            'additionalRecords' => $this->getAdditionalRecords(),
            'authorityRecords' => $this->getAuthorityRecords(),
            'answerRecords' => $this->getAnswerRecords(),
        ];
    }

    /**
     * Magic method for unserialize
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->questions = $data['questions'];
        $this->additionalRecords = $data['additionalRecords'];
        $this->authorityRecords = $data['authorityRecords'];
        $this->answerRecords = $data['answerRecords'];
    }
}
