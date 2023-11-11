<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Exceptions\EmptyArgumentException;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketQuestionInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordClassInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQTypeDefinitionInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use ArrayAccess\DnsRecord\Utils\Addresses;
use ArrayAccess\DnsRecord\Utils\Buffer;
use ArrayAccess\DnsRecord\Utils\Lookup;
use ReflectionClass;
use Throwable;
use function preg_match;
use function serialize;
use function strtolower;
use function trim;
use function unserialize;

/**
 * Question format
 *
 *                                     1  1  1  1  1  1
 *       0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                                               |
 *      /                     QNAME                     /
 *      /                                               /
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                     QTYPE                     |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                     QCLASS                    |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-4.1.2
 */
class Question implements PacketQuestionInterface
{
    /**
     * @see PacketQuestionInterface::getName()
     * @var string
     */
    public readonly string $name;

    /**
     * @see PacketQuestionInterface::getMessage()
     * @var string
     */
    public readonly string $message;

    /**
     * @see PacketQuestionInterface::getType()
     */
    public readonly ?ResourceRecordQTypeDefinitionInterface $type;

    /**
     * @see PacketQuestionInterface::getClass()
     * @var ?ResourceRecordClassInterface
     */
    public readonly ?ResourceRecordClassInterface $class;

    /**
     * @inheritdoc
     */
    public function __construct(
        string $name,
        string|Int|ResourceRecordTypeInterface|ResourceRecordQTypeDefinitionInterface $type,
        string|int|ResourceRecordClassInterface $class,
        bool $disableInit = false
    ) {
        if (!$disableInit) {
            /**
             * @uses self::init()
             */
            $this->init($name, $type, $class);
        }
    }

    private function initType(
        string|Int|ResourceRecordTypeInterface|ResourceRecordQTypeDefinitionInterface $type,
    ): void {
        $this->type ??= Lookup::resourceType($type);
    }

    private function initClass(string|Int|ResourceRecordClassInterface $type): void
    {
        if (isset($this->class)) {
            return;
        }

        $this->class ??= Lookup::resourceClass($type);
    }

    private function initName(string $name, bool $internal = false): void
    {
        $name  = trim($name);
        if (!$internal && $name === '') {
            throw new EmptyArgumentException(
                'Argument QNAME could not be empty or whitespace only'
            );
        }

        $name = strtolower($name);
        if (($this->type??null) === 'PTR' && !preg_match('~\.(in-addr|ip6)\.arpa$~', $name)) {
            $name = Addresses::reverseIp($name)??$name;
        }
        $this->name = $name;
    }

    /**
     * Init constructor
     *
     * @param string $name
     * @param string|Int|ResourceRecordTypeInterface|ResourceRecordQTypeDefinitionInterface $type
     * @param string|int|ResourceRecordClassInterface $class
     * @return void
     */
    private function init(
        string $name,
        string|Int|ResourceRecordTypeInterface|ResourceRecordQTypeDefinitionInterface $type,
        string|int|ResourceRecordClassInterface $class
    ): void {
        $this->initType($type);
        $this->initClass($class);
        $this->initName($name);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getType(): ?ResourceRecordQTypeDefinitionInterface
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getClass(): ?ResourceRecordClassInterface
    {
        return $this->class;
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return $this->message ??= Buffer::createQuestionMessage($this);
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Magic method for serializing
     *
     * @return array{name:string, type:string, class:string}
     */
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'class' => $this->class,
        ];
    }

    /**
     * @param string $name
     * @param int $type
     * @param int $class
     * @return Question
     */
    public static function fromFilteredResponse(
        string $name,
        int $type,
        int $class
    ): self {
        /** @noinspection PhpUnhandledExceptionInspection */
        $object = (new ReflectionClass(__CLASS__))
            ->newInstanceWithoutConstructor();
        /**
         * @var Question $object
         */
        try {
            $object->initType($type);
        } catch (Throwable) {
            $object->type = null;
        }
        try {
            $object->initClass($class);
        } catch (Throwable) {
            $object->class = null;
        }
        $object->initName($name, true);
        return $object;
    }

    /**
     * Magic method for unserialize
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->init($data['name'], $data['type'], $data['class']);
    }
}
