<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Exceptions\LengthException;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketHeaderInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketMessageInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordOpcodeInterface;
use ArrayAccess\DnsRecord\Utils\Buffer;
use ArrayAccess\DnsRecord\Utils\Lookup;
use function array_search;
use function base64_decode;
use function base64_encode;
use function implode;
use function is_string;
use function ord;
use function serialize;
use function sprintf;
use function strlen;
use function substr;
use function unserialize;

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
class Header implements PacketHeaderInterface
{
    /**
     * @see PacketHeaderInterface::getMessage()
     *
     * @var ?string
     */
    protected ?string $message;

    /**
     * @see PacketHeaderInterface::getId()
     * @var int
     */
    protected int $id;

    /**
     * @see PacketHeaderInterface::getQR()
     *
     * @var int
     */
    protected int $qr;

    /**
     * @see PacketHeaderInterface::getOpCode()
     * @var ResourceRecordOpcodeInterface
     */
    protected ResourceRecordOpcodeInterface $opcode;

    /**
     * @see PacketHeaderInterface::getAA()
     * @var int
     */
    protected int $aa;

    /**
     * @see PacketHeaderInterface::getTC()
     * @var int
     */
    protected int $tc;

    /**
     * @see PacketHeaderInterface::getRD()
     * @var int
     */
    protected int $rd;

    /**
     * @see PacketHeaderInterface::getRA()
     * @var int
     */
    protected int $ra;

    /**
     * @see PacketHeaderInterface::getZ()
     * @var int
     */
    protected int $z;

    /**
     * @see PacketHeaderInterface::getAD()
     * @var int
     */
    protected int $ad;

    /**
     * @see PacketHeaderInterface::getCD()
     * @var int
     */
    protected int $cd;

    /**
     * @see PacketHeaderInterface::getRCode()
     * @var int
     */
    protected int $rcode;

    /**
     * @var string
     */
    protected string $rcodeName;

    /**
     * @see PacketHeaderInterface::getQDCount()
     * @var int
     */
    protected int $qdcount;

    /**
     * @see PacketHeaderInterface::getAnCount()
     * @var int
     */
    protected int $ancount;

    /**
     * @see PacketHeaderInterface::getNSCount()
     * @var int
     */
    protected int $nscount;

    /**
     * @see PacketHeaderInterface::getARCount()
     * @var int
     */
    protected int $arcount;


    /**
     * Maximum 16-bit integer = 65535
     *
     * @var int
     */
    private static int $increment = 0;

    /**
     * Header constructor
     */
    private function __construct()
    {
    }

    /**
     * Return standard dig header
     *
     * @return string
     */
    public function __toString(): string
    {
        $content = sprintf(
            ";; ->>HEADER<<- opcode: %s, status: %s, id: %d\n",
            array_search($this->opcode, Lookup::OPCODE_LIST, true)?:'UNKNOWN',
            $this->rcodeName,
            $this->id
        );

        // flags
        $flags = [];
        $this->qr && $flags[] = 'qr';
        $this->aa && $flags[] = 'aa';
        $this->tc && $flags[] = 'tc';
        $this->rd && $flags[] = 'rd';
        $this->ra && $flags[] = 'ra';

        $content .= sprintf(
            ";; flags: %s; QUERY: %d; ANSWER: %d; AUTHORITY: %d; ADDITIONAL: %d\n",
            implode(' ', $flags),
            $this->qdcount,
            $this->ancount,
            $this->nscount,
            $this->arcount
        );

        return $content;
    }

    /**
     * Parse the message
     *
     * @param string $message
     * @return $this
     */
    private function parseMessage(string $message) : static
    {
        $message = substr($message, 0, Lookup::HEADER_SIZE);
        $this->message = $message;

        // initial offset
        $offset = 0;

        // first table
        // id on first header unsigned short 16 bit big endian
        $this->id = ord($message[$offset]) << 8 | ord($message[++$offset]);

        $flags = ord($message[++$offset]);
        // getting 4-bit op code
        $this->opcode   = Lookup::resourceOpcode($flags >> 3 & 15);
        /**
         * getting 1-bit at position 7
         * On response @uses Lookup::QR_RESPONSE
         */
        $this->qr       = $flags >> 7 & 1;
        $this->aa       = $flags >> 2 & 1;
        $this->tc       = $flags >> 1 & 1;
        $this->rd       = $flags & 1;

        $flags = ord($message[++$offset]);
        $this->ra       = $flags >> 7 & 1;
        $this->z        = $flags >> 6 & 1;
        $this->ad       = $flags >> 5 & 1;
        $this->cd       = $flags >> 4 & 1;
        $this->rcode    = $flags & 15;

        $this->qdcount  = ord($message[++$offset]) << 8 |
            ord($message[++$offset]);
        $this->ancount  = ord($message[++$offset]) << 8 |
            ord($message[++$offset]);
        $this->nscount  = ord($message[++$offset]) << 8 |
            ord($message[++$offset]);
        $this->arcount  = ord($message[++$offset]) << 8 |
            ord($message[++$offset]);
        $this->rcodeName = array_search($this->rcode, Lookup::RCODE_LIST, true)?:'';
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function fromMessage(PacketMessageInterface|string $message): static
    {
        if (!is_string($message)) {
            $message = $message->getMessage();
        }
        if (($length = strlen($message)) < Lookup::HEADER_SIZE) {
            throw new LengthException(
                sprintf(
                    'Response data length is too small: %d',
                    $length
                )
            );
        }

        return (new self())->parseMessage($message);
    }

    /**
     * @inheritdoc
     */
    public static function createQueryHeader(
        int|string|ResourceRecordOpcodeInterface $opcode = Lookup::OPCODE_QUERY,
        ?int $id = null
    ) : PacketHeaderInterface {
        if ($id === null) {
            // 160-bit max 65535 -> then reset to 1
            if (++self::$increment > 65535) {
                self::$increment = 1;
            }
            $id = self::$increment;
        }

        $id = $id % 65535;
        $id = $id === 0 ? 65535 : $id;

        $opcode = Lookup::resourceOpcode($opcode);
        $header = new self();
        $header->id = $id;
        $header->qr = Lookup::QR_QUERY; // the opcode
        $header->opcode = $opcode;
        $header->aa = 0;
        $header->tc = 0;
        $header->z = 0; // always 0
        $header->qdcount = 1;
        $header->rd = 1; // recursion desired
        $header->ad = 1;
        $header->cd = 0;
        $header->ra = 0; // ra is zero cause query
        $header->rcode = Lookup::RCODE_NOERROR; // use no error
        $header->ancount = 0; // ancount is 0 because the query
        $header->nscount = 0;
        $header->arcount = 0;
        if ($header->opcode->getValue() === Lookup::OPCODE_DSO) {
            // https://datatracker.ietf.org/doc/html/rfc8490#section-5.4
            // qdcount, ancount, nscount & arcount must be zero on dso
            $header->rd = 0;
            $header->qdcount = 0;
        } elseif ($header->opcode->getValue() === Lookup::OPCODE_UPDATE) {
            $header->rd = 0; // recursion desired should 0 in update
        }
        $header->rcodeName = 'NOERROR';
        return $header;
    }

    /**
     * @inheritdoc
     */
    public function withARCount(int $ar) : static
    {
        $obj = clone $this;
        $obj->arcount = $ar;
        $obj->message = null;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function withANCount(int $an): static
    {
        $obj = clone $this;
        $obj->ancount = $an;
        $obj->message = null;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function withQDCount(int $qd) : static
    {
        $obj = clone $this;
        $obj->qdcount = $qd;
        $obj->message = null;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function withADFlag(bool $ad): static
    {
        $obj = clone $this;
        $obj->ad = $ad ? 1 : 0;
        $obj->message = null;
        return $obj;
    }

    public function withAAFlag(bool $aa) : static
    {
        $obj = clone $this;
        $obj->aa = $aa ? 1 : 0;
        $obj->message = null;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function withCDFlag(bool $cd): static
    {
        $obj = clone $this;
        $obj->cd = $cd ? 1 : 0;
        $obj->message = null;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function withId(int $id): static
    {
        $obj = clone $this;
        $c = $id % 65535;
        $obj->id = $c === 0 ? 65535 : $c;
        $obj->message = null;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return $this->message??= Buffer::createHeaderMessage($this);
    }

    /**
     * @inheritdoc
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getQR(): int
    {
        return $this->qr;
    }

    /**
     * @inheritdoc
     */
    public function getOpCode(): ResourceRecordOpcodeInterface
    {
        return $this->opcode;
    }

    /**
     * @inheritdoc
     */
    public function getAA(): int
    {
        return $this->aa;
    }

    /**
     * @inheritdoc
     */
    public function getTC(): int
    {
        return $this->tc;
    }

    /**
     * @inheritdoc
     */
    public function getRD(): int
    {
        return $this->rd;
    }

    /**
     * @inheritdoc
     */
    public function getRA(): int
    {
        return $this->ra;
    }

    /**
     * @inheritdoc
     */
    public function getZ(): int
    {
        return $this->z;
    }

    /**
     * @inheritdoc
     */
    public function getAD(): int
    {
        return $this->ad;
    }

    /**
     * @inheritdoc
     */
    public function getCD(): int
    {
        return $this->cd;
    }

    /**
     * @inheritdoc
     */
    public function getRCode(): int
    {
        return $this->rcode;
    }

    /**
     * @inheritdoc
     */
    public function getQDCount(): int
    {
        return $this->qdcount;
    }

    /**
     * @inheritdoc
     */
    public function getAnCount(): int
    {
        return $this->ancount;
    }

    /**
     * @inheritdoc
     */
    public function getNSCount(): int
    {
        return $this->nscount;
    }

    /**
     * @inheritdoc
     */
    public function getARCount(): int
    {
        return $this->arcount;
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     * @return void
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
        return ['message' => base64_encode($this->getMessage())];
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
        $this->parseMessage(base64_decode($data['message']));
    }
}
