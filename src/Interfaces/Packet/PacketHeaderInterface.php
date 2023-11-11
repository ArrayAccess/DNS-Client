<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordOpcodeInterface;
use ArrayAccess\DnsRecord\Utils\Lookup;
use Serializable;

/**
 * Header response contains 12 bits
 * RFC 5395 Section 2
 *
 *                                      1  1  1  1  1  1
 *        0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                      ID                       |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |QR|   OpCode  |AA|TC|RD|RA| Z|AD|CD|   RCODE   |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |              QDCOUNT/ZOCOUNT                  |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |               ANCOUNT/PRCOUNT                 |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |               NSCOUNT/UPCOUNT                 |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *      |                    ARCOUNT                    |
 *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * In [UPDATE] Request ARCOUNT = ADCOUNT
 *
 * @link https://datatracker.ietf.org/doc/html/rfc5395#section-2
 */
interface PacketHeaderInterface extends Serializable
{
    /**
     * Create a new Header object from given raw data
     *
     * @param PacketMessageInterface|string $message the raw message from dns response
     * @return static
     */
    public static function fromMessage(PacketMessageInterface|string $message): static;

    /**
     * Crate query header for request dns server
     *
     * @param int|string $opcode
     * @param ?int $id
     * @return PacketHeaderInterface
     */
    public static function createQueryHeader(
        int|string $opcode = Lookup::OPCODE_QUERY,
        ?int $id = null
    ) : PacketHeaderInterface;

    /**
     * 12-bits raw data header from dns
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     *  A 16-bit identifier assigned by the program that
     *  generates any kind of query.  This identifier is copied
     *  the corresponding reply and can be used by the requester
     *  to match up replies to outstanding queries.
     *
     *  (RequestId === ResponseId)
     * @return int
     */
    public function getId(): int;

    /**
     * A one-bit field that specifies whether this message is a
     *  query (0), or a response (1)
     *
     * @return int<0|1>
     */
    public function getQR(): int;

    /**
     * A 4-bit field that specifies kind of query in this
     * message.  This value is set by the originator of a query
     * and copied into the response.
     *
     * (0) a standard query (QUERY) - RFC 1035
     *
     * (1) an inverse query (IQUERY) - RFC 1035
     *
     * (2) a server status request (STATUS) - RFC 1035
     *
     * (4) a notify query (NOTIFY) - RFC 1996
     *
     * (5) an update query (UPDATE) - RFC 2136
     *
     *      UPDATE header format RFC 2136 Section 2
     *      ADCOUNT = ARCOUNT
     *
     *                                      1  1  1  1  1  1
     *        0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |                      ID                       |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |QR| OPCODE(5) |          Z         |   RCODE   |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |               QDCOUNT/ZOCOUNT                 |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |                ANCOUNT/PRCOUNT                |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |              NSCOUNT/UPCOUNT                  |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |                    ADCOUNT                    |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *
     * (6) a dso query (DSO) - RFC 8490
     *      DSO header format RFC 8490 Section 5.4
     *
     *                                      1  1  1  1  1  1
     *        0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |                   MESSAGE ID                  |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |QR | OPCODE(6) |        Z          |   RCODE   |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |              QDCOUNT (MUST be zero)           |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |              ANCOUNT (MUST be zero)           |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |             NSCOUNT (MUST be zero)            |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |             ARCOUNT (MUST be zero)            |
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *      |                                               |
     *      /                 DSO Data                      /
     *      /                                               /
     *      +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
     *
     * (3 & 7-15) reserved for future use
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1996
     * @link https://datatracker.ietf.org/doc/html/rfc2136
     * @link https://datatracker.ietf.org/doc/html/rfc8490
     *
     * @return ResourceRecordOpcodeInterface
     */
    public function getOpCode(): ResourceRecordOpcodeInterface;

    /**
     * Authoritative Answer - this bit is valid in responses,
     *  and specifies that the responding name server is an
     *  authority for the domain name in a question section.
     *
     * @return int
     */
    public function getAA(): int;

    /**
     * TrunCation - specifies that this message was truncated
     *  due to length greater than that permitted on the
     *  transmission channel.
     *
     * @return int
     */
    public function getTC(): int;

    /**
     * Recursion Desired - this bit may be set in a query and
     *  is copied into the response.  If RD is set, it directs
     *  the name server to pursue the query recursively.
     *  Recursive query support is optional.
     *
     * @return int
     */
    public function getRD(): int;

    /**
     * Recursion Available - this be being set or cleared in a
     *  response, and denotes whether recursive query support is
     *  available in the name server.
     *
     * @return int
     */
    public function getRA(): int;

    /**
     * Z is Reserved for future use.  Must be zero in all queries
     *  and responses.
     * @return int
     */
    public function getZ(): int;

    /**
     * Authentic data - indicates in a response that the server has verified the data included.
     *
     * @return int
     */
    public function getAD(): int;

    /**
     * Checking Disabled - indicates in a query that non-verified data is acceptable to the resolver.
     *
     * @return int
     */
    public function getCD(): int;

    /**
     * Response code - this 4-bit field is set as part of responses.
     * The values have the following interpretation:
     *
     * | RCODE  | Name     | Description                       | Reference |
     * |--------|----------|-----------------------------------|-----------|
     * | 0      | NoError  | No Error                          | [RFC1035] |
     * | 1      | FormErr  | Format Error                      | [RFC1035] |
     * | 2      | ServFail | Server Failure                    | [RFC1035] |
     * | 3      | NXDomain | Non-Existent Domain               | [RFC1035] |
     * | 4      | NotImp   | Not Implemented                   | [RFC1035] |
     * | 5      | Refused  | Query Refused                     | [RFC1035] |
     * | 6      | YXDomain | Name Exists when it should not    | [RFC2136] |
     * | 7      | YXRRSet  | RR Set Exists when it should not  | [RFC2136] |
     * | 8      | NXRRSet  | RR Set that should exist does not | [RFC2136] |
     * | 9      | NotAuth  | Server Not Authoritative for zone | [RFC2136] |
     * | 10     | NotZone  | Name not contained in zone        | [RFC2136] |
     * | 11     | DSOTYPENI| DSO-TYPE Not Implemented          | [RFC8490] |
     * | 12-15  |          | Available for assignment          |           |
     * | 16     | BADVERS  | Bad OPT Version                   | [RFC2671] |
     * | 16     | BADSIG   | TSIG Signature Failure            | [RFC2845] |
     * | 17     | BADKEY   | Key not recognized                | [RFC2845] |
     * | 18     | BADTIME  | Signature out of time window      | [RFC2845] |
     * | 19     | BADMODE  | Bad TKEY Mode                     | [RFC2930] |
     * | 20     | BADNAME  | Duplicate key name                | [RFC2930] |
     * | 21     | BADALG   | Algorithm not supported           | [RFC2930] |
     * | 22     | BADTRUNC | Bad Truncation                    | [RFC4635] |
     * | 23     | BADCOOKIE| Bad Cookie                        | [RFC7873] |
     *
     * 24 - 3,840
     * 0x0017 - 0x0F00 - Available for assignment
     *
     * 3,841 - 4,095
     * 0x0F01 - 0x0FFF - Private Use
     *
     * 4,096 - 65,534
     * 0x1000 - 0xFFFE - Available for assignment
     *
     * 65,535
     * 0xFFFF - Reserved, can only be allocated by an IETF
     * Standards Action.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5395#section-2.3
     *
     * @return int
     */
    public function getRCode(): int;

    /**
     * An unsigned 16-bit integer specifying the number of entries in the question section.
     *
     * @return int
     */
    public function getQDCount(): int;

    /**
     * An unsigned 16-bit integer specifying the number of resource records in the answer section.
     *
     * @return int
     */
    public function getAnCount(): int;

    /**
     * An unsigned 16-bit integer specifying the number of name server resource records
     *   in the authority records section.
     *
     * @return int
     */
    public function getNSCount(): int;

    /**
     * An unsigned 16-bit integer specifying the number of
     *  resource records in the additional records section.
     *
     * @return int
     */
    public function getARCount(): int;

    /**
     * With ARCOUNT, this for additional questions records
     *
     * @param int $ar
     * @return $this
     */
    public function withARCount(int $ar) : static;

    /**
     * With Answer count
     *
     * @param int $an
     * @return $this
     */
    public function withANCount(int $an) : static;

    /**
     * With QD Count
     * @param int $qd
     * @return $this
     */
    public function withQDCount(int $qd) : static;

    /**
     * With additional data flags
     *
     * @param bool $ad
     * @return $this
     */
    public function withADFlag(bool $ad) : static;

    /**
     * With AA Flag
     *
     * @param bool $aa
     * @return $this
     */
    public function withAAFlag(bool $aa) : static;

    /**
     * With checking disabled
     *
     * @param bool $cd
     * @return $this
     */
    public function withCDFlag(bool $cd) : static;

    /**
     * With recursion desired
     *
     * @param bool $rd
     * @return $this
     */
    public function withRDFlag(bool $rd) : static;

    /**
     * With identity
     * 1 - 65535
     *
     * @param int $id
     * @return $this
     */
    public function withId(int $id) : static;
}
