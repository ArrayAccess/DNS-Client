<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use Serializable;

/**
 * Packet response interface
 * Serializable to store into cache data
 */
interface PacketResponseInterface extends Serializable
{

    /**
     * Get requested packet data
     *
     * @return PacketRequestDataInterface
     */
    public function getPacketData() : PacketRequestDataInterface;

    /**
     * Get used protocol
     *
     * @return string<"udp"|"tcp">
     */
    public function getProtocol() : string;

    /**
     * Get used server
     *
     * @return string
     */
    public function getServer() : string;

    /**
     * Get DNS Server port
     *
     * @return int
     */
    public function getPort() : int;

    /**
     * Get query execute query time
     * $start     = microtime(true) * 1000;
     * $queryTime = microtime(true) * 1000 - $start;
     *
     * @return float
     */
    public function getQueryTime() : float;

    /**
     * Get Packet answer
     *
     * @return PacketAnswersInterface
     */
    public function getAnswers() : PacketAnswersInterface;

    /**
     * Get Start Query time
     *
     * @return float
     */
    public function getStartTime() : float;

    /**
     * Get end of query time
     *
     * @return float
     */
    public function getEndTime() : float;
}
