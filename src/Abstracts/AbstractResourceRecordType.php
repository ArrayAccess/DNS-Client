<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Abstracts;

use ArrayAccess\DnsRecord\Exceptions\LengthException;
use ArrayAccess\DnsRecord\Exceptions\MalformedDataException;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketMessageInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordClassInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordMetaTypeInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQTypeDefinitionInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use ArrayAccess\DnsRecord\Utils\Buffer;
use ArrayAccess\DnsRecord\Utils\Lookup;
use function is_array;
use function ord;
use function serialize;
use function sprintf;
use function strlen;
use function substr;
use function unpack;
use function unserialize;

abstract class AbstractResourceRecordType implements ResourceRecordTypeInterface
{
    /**
     * @const string|int
     */
    const TYPE = null;

    /**
     * The response name
     *
     * @var string
     * @see ResourceRecordTypeInterface::getName()
     */
    protected string $name;

    /**
     * The response header
     *
     * @var string
     */
    protected string $header;

    /**
     * Response type
     *
     * @var ResourceRecordQTypeDefinitionInterface
     * @see ResourceRecordTypeInterface::getType()
     * @see Lookup::RR_TYPES
     */
    protected ResourceRecordQTypeDefinitionInterface $type;

    /**
     * @var ResourceRecordClassInterface
     * @see ResourceRecordTypeInterface::getClass()
     * @see Lookup::RR_CLASS_*
     */
    protected ResourceRecordClassInterface $class;

    /**
     * @var int
     * @see ResourceRecordTypeInterface::getTTL()
     */
    protected int $ttl = self::DEFAULT_TTL;

    /**
     * @var int
     * @see ResourceRecordTypeInterface::getRDLength()
     */
    protected int $rdLength;

    /**
     * @var string
     * @see ResourceRecordTypeInterface::getRData()
     */
    protected string $rData;

    /**
     * Record value
     *
     * @var ?string
     */
    protected ?string $value = null;

    /**
     * @inheritdoc
     * @throws MalformedDataException
     */
    public function __construct(
        protected readonly PacketMessageInterface $message,
        protected readonly int $offsetPosition
    ) {
        $this->parseMessage();
    }

    public function isMetaType() : bool
    {
        return $this instanceof ResourceRecordMetaTypeInterface;
    }

    public function getOffsetPosition(): int
    {
        return $this->offsetPosition;
    }

    /**
     * @throws MalformedDataException
     */
    protected function parseMessage(): void
    {
        $type = static::TYPE;
        $message = $this->message->getMessage();
        $offsetPosition = $this->offsetPosition;
        $this->name   = Buffer::readLabel($message, $offsetPosition);
        $this->header = Buffer::read($message, $offsetPosition, 10);
        if (strlen($this->header) !== 10) {
            throw new LengthException(
                'Response header length is invalid'
            );
        }
        $headerArray = unpack("ntype/nclass/Nttl/nlength", $this->header);
        $this->rdLength = 0;
        if (is_array($headerArray)) {
            [
                'type' => $type,
                'class' => $class,
                'ttl' => $this->ttl,
                'length' => $this->rdLength,
            ] = $headerArray;
        }

        $this->rData = substr($message, $offsetPosition, $this->rdLength);
        if (strlen($this->rData) !== $this->rdLength) {
            throw new LengthException(
                'Rdata & length from response header is mismatch'
            );
        }

        $type  = $type ? Lookup::resourceType($type) : null;
        $class = Lookup::resourceClass($class??'');
        if (isset($this->type) || !$type) {
            if ($this->type->getName() !== $type?->getName()) {
                throw new MalformedDataException(
                    sprintf(
                        'Response type does not match with current object type. object type: [%s] response type: [%s]',
                        $this->type->getName(),
                        $type
                    )
                );
            }
        } else {
            $this->type = $type;
        }

        $this->class = $class;
        $this->parseRData($message, $offsetPosition);
    }

    /**
     * Parse the data
     *
     * @param string $message
     * @param int $rdataOffset
     * @noinspection PhpMissingReturnTypeInspection
     * @phpstan-ignore-next-line
     */
    protected function parseRData(string $message, int $rdataOffset)
    {
        $length = strlen($message);
        if ($this->rdLength < 1 || $length < ($rdataOffset+1)) {
            return;
        }

        $offset = $rdataOffset;
        $xLen = ord($message[$offset]);
        ++$offset;
        if (($xLen + $offset) > $length) {
            $this->value = substr($message, $offset);
        } else {
            $this->value = substr($message, $offset, $xLen);
        }
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name??'';
    }

    /**
     * @inheritdoc
     */
    public function getHeader(): string
    {
        return $this->header??'';
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): PacketMessageInterface
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function getType(): ResourceRecordQTypeDefinitionInterface
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getClass(): ResourceRecordClassInterface
    {
        return $this->class;
    }

    /**
     * @inheritdoc
     */
    public function getTTL(): int
    {
        return $this->ttl;
    }

    /**
     * @inheritdoc
     */
    public function getRDLength(): int
    {
        return $this->rdLength ??= strlen($this->getRData());
    }

    /**
     * Resource data
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function getRData(): string
    {
        return $this->rData??'';
    }

    public function getQueryMessage() : string
    {
        return $this->rData??'';
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @throws MalformedDataException
     */
    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Magic method for serializing
     *
     * @return array{message: PacketMessageInterface, offsetPosition: int}
     */
    public function __serialize(): array
    {
        return [
            'message' => $this->message,
            'offsetPosition' => $this->offsetPosition,
        ];
    }

    /**
     * Magic method for unserialize
     *
     * @param array{message: PacketMessageInterface, offsetPosition: int} $data
     * @return void
     * @throws MalformedDataException
     */
    public function __unserialize(array $data): void
    {
        $this->message = $data['message'];
        $this->offsetPosition = $data['offsetPosition'];
        $this->parseMessage();
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
            'value' => $this->getValue(),
        ];
    }
}
