<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use function substr;

/**
 * DNS Certification Authority Authorization (CAA) Resource Record - CAA RFC6844
 *
 *      +0-1-2-3-4-5-6-7-|0-1-2-3-4-5-6-7-|
 *      | Flags          | Tag Length = n |
 *      +----------------+----------------+...+---------------+
 *      | Tag char 0     | Tag Char 1     |...| Tag Char n-1  |
 *      +----------------+----------------+...+---------------+
 *      +----------------+----------------+.....+---------------+
 *      | Data byte 0    | Data byte 1    |.....| Data byte m-1 |
 *      +----------------+----------------+.....+---------------+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc6844#section-3
 */
class CAA extends AbstractResourceRecordType
{
    const TYPE = 'CAA';

    /**
     * @var int
     */
    protected int $flags;

    /**
     * @var string
     */
    protected string $tag;

    /**
     * @var int
     */
    protected int $tagLength;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        // unpack the flags and tag length
        [
            'flags' => $this->flags,
            'tagLength' => $this->tagLength,

        ] = unpack('Cflags/CtagLength', substr($this->rData, 0, 2));
        $this->tag      = substr($this->rData, 2, $this->tagLength);
        $this->value    = substr($this->rData, 2 + $this->tagLength);
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getTagLength(): int
    {
        return $this->tagLength;
    }

    /**
     * @return array{
     *     host:string,
     *     class:string,
     *     ttl:int,
     *     type:string,
     *     flags:int,
     *     tag:string,
     *     value:string,
     * }
     */
    public function toArray(): array
    {
        return [
            'host' => $this->getName(),
            'class' => $this->getClass()->getName(),
            'ttl' => $this->getTTL(),
            'type' => $this->getType()->getName(),
            'flags' => $this->getFlags(),
            'tag' => $this->getTag(),
            'value' => $this->getValue(),
        ];
    }
}
