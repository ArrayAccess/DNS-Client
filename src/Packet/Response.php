<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Interfaces\Packet\PacketAnswersInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResponseInterface;
use function serialize;
use function unserialize;

class Response implements PacketResponseInterface
{
    protected PacketRequestDataInterface $packetData;

    protected ?int $cacheTime = null;

    public function __construct(
        protected float $startTime,
        protected float $endTime,
        protected string $protocol,
        protected string $server,
        protected int $port,
        PacketRequestDataInterface $packetData,
        protected PacketAnswersInterface $answers
    ) {
        if (!$packetData instanceof RequestDataWrapper) {
            $packetData = new RequestDataWrapper($packetData);
        }
        $this->packetData = $packetData;
    }

    public function getPacketData(): PacketRequestDataInterface
    {
        return clone $this->packetData;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getQueryTime(): float
    {
        return $this->endTime - $this->startTime;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getEndTime(): float
    {
        return $this->endTime;
    }

    public function getAnswers(): PacketAnswersInterface
    {
        return $this->answers;
    }


    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * @return array{
     *     startTime: float,
     *     endTime: float,
     *     protocol: string,
     *     server: string,
     *     port: int,
     *     packetData: PacketRequestDataInterface,
     *     answers: PacketAnswersInterface,
     *     time: int
     * }
     */
    public function __serialize(): array
    {
        return [
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'protocol' => $this->protocol,
            'server' => $this->server,
            'port' => $this->port,
            'packetData' => $this->packetData,
            'answers' => $this->answers,
            'time' => time()
        ];
    }

    /**
     * Magic method for unserialize
     *
     * @param array{
     *      startTime: float,
     *      endTime: float,
     *      protocol: string,
     *      server: string,
     *      port: int,
     *      packetData: PacketRequestDataInterface,
     *      answers: PacketAnswersInterface,
     *      time: int
     *  } $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->startTime = $data['startTime'];
        $this->endTime = $data['endTime'];
        $this->protocol = $data['protocol'];
        $this->server = $data['server'];
        $this->port = $data['port'];
        $this->packetData = $data['packetData'];
        $this->answers = $data['answers'];
        $this->cacheTime = $data['time'];
    }
}
