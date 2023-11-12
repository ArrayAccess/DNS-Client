<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\ResourceRecord;

use ArrayAccess\DnsRecord\Interfaces\Packet\PacketMessageInterface;
use Serializable;

/**
 * Resource record type
 *
 * DNS resource record format - RFC1035 section 4.1.3
 *
 * https://datatracker.ietf.org/doc/html/rfc1035#section-4.1.3
 *
 *                                     1  1  1  1  1  1
 *       0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *     +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *     |                                               |
 *     /                                               /
 *     /                      NAME                     /
 *     |                                               |
 *     +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *     |                      TYPE                     |
 *     +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *     |                     CLASS                     |
 *     +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *     |                      TTL                      |
 *     |                                               |
 *     +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *     |                   RDLENGTH                    |
 *     +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--|
 *     /                     RDATA                     /
 *     /                                               /
 *     +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * Serializable to make sure the Record cacheable
 */
interface ResourceRecordTypeInterface extends Serializable
{
    const DEFAULT_TTL = 86400;

    /**
     * ResourceRecordInterface constructor
     *
     * @param PacketMessageInterface $message
     * @param int $offsetPosition
     */
    public function __construct(
        PacketMessageInterface $message,
        int $offsetPosition
    );

    /**
     * Check if meta-type
     *
     * @return bool
     */
    public function isMetaType() : bool;

    /**
     * Get answer record position
     *
     * @return int
     */
    public function getOffsetPosition() : int;

    /**
     * The header from record response
     *
     * @return string
     * @see \ArrayAccess\DnsRecord\Packet\Answers::parseRecords()
     */
    public function getHeader() : string;

    /**
     * Get message raw all response answer data
     *
     * @return PacketMessageInterface
     */
    public function getMessage(): PacketMessageInterface;

    /**
     * The dns name
     *
     * @return string
     * @see \ArrayAccess\DnsRecord\Packet\Answers::parseRecords()
     */
    public function getName() : string;

    /**
     * The dns type
     *
     * @return ResourceRecordQTypeDefinitionInterface
     */
    public function getType() : ResourceRecordQTypeDefinitionInterface;

    /**
     * The dns class type
     *
     * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#dns-parameters-2
     * @rfc6895 @link https://www.rfc-editor.org/rfc/rfc6895.html
     * @rfc9108 @link https://www.rfc-editor.org/rfc/rfc9108.html
     * @return ResourceRecordClassInterface
     */
    public function getClass() : ResourceRecordClassInterface;

    /**
     * DNS record time to live "ttl"
     *
     * @return int
     */
    public function getTTL() : int;

    /**
     * Get resource length, formerly "rdlength"
     *
     * @return int
     */
    public function getRDLength() : int;

    /**
     * Resource data
     * @phpstan-ignore-next-line
     */
    public function getValue();

    /**
     * The raw data of dns content, formerly "rdata"
     *
     * @return string
     */
    public function getRData() : string;

    /**
     * Get query Message
     *
     * @return string
     */
    public function getQueryMessage() : string;

    /**
     * Return array data
     *
     * @return array<string, mixed> like dns_get_record()
     * @see \dns_get_record()
     */
    public function toArray() : array;
}
