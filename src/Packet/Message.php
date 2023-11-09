<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Interfaces\Packet\PacketMessageInterface;
use function base64_decode;
use function base64_encode;
use function unserialize;

final class Message implements PacketMessageInterface
{
    /**
     * @param string $message raw data from DNS response
     */
    public function __construct(public readonly string $message)
    {
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function __toString(): string
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function serialize() : string
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
     * @return array{message: string}
     * @uses base64_encode() to encode raw data
     */
    public function __serialize(): array
    {
        return ['message' => base64_encode($this->message)];
    }

    /**
     * Magic method for unserialize
     *
     * @param array $data
     * @return void
     * @uses base64_decode() decode to raw data
     */
    public function __unserialize(array $data): void
    {
        $this->message = base64_decode($data['message']);
    }
}
