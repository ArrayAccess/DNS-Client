<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Exceptions\MalformedDataException;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketAnswersInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketHeaderInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketMessageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketQuestionInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResourceRecordsInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use ArrayAccess\DnsRecord\ResourceRecord\RRTypes\RRDefault;
use ArrayAccess\DnsRecord\Utils\Buffer;
use ArrayAccess\DnsRecord\Utils\Lookup;
use Throwable;
use function array_pop;
use function class_exists;
use function explode;
use function implode;
use function is_string;
use function is_subclass_of;
use function ord;
use function serialize;
use function sprintf;
use function strlen;
use function substr;
use function unpack;
use function unserialize;

final class Answers implements PacketAnswersInterface
{
    /**
     * Full message response
     *
     * @var PacketMessageInterface
     */
    public readonly PacketMessageInterface $message;

    /**
     * Response Header
     *
     * @var PacketHeaderInterface
     */
    private PacketHeaderInterface $header;

    /**
     * Response Header Question
     *
     * @var array<PacketQuestionInterface>
     */
    private array $questions;

    /**
     * RR Responses
     *
     * @var PacketResourceRecordsInterface
     */
    private PacketResourceRecordsInterface $records;

    /**
     * @var string
     */
    private static string $namespace;

    private int $offset = 0;

    public function __construct(string|PacketMessageInterface $message)
    {
        $this->message = is_string($message) ? new Message($message) : $message;
    }

    /**
     * @inheritdoc
     */
    public function isTruncated(): bool
    {
        return $this->getHeader()->getTC() > 0;
    }

    /**
     * Parse the header, record & add object record
     *
     * @return PacketHeaderInterface
     */
    private function parseHeader(): PacketHeaderInterface
    {
        if (isset($this->header)) {
            return $this->header;
        }

        $this->offset = 0;
        $this->header = Header::fromMessage($this->message);
        /*
        if ($this->header->qr !== Lookup::QR_RESPONSE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Answer (QR) message should be response, %s',
                    $this->header->qr === Lookup::QR_QUERY
                        ? 'not as a query request'
                        : 'response QR unknown type'
                )
            );
        }*/

        return $this->header;
    }

    /**
     * Parse the response question
     *
     * @throws MalformedDataException
     * @return PacketQuestionInterface[]
     */
    private function parseQuestions() : array
    {
        if (isset($this->questions)) {
            return $this->questions;
        }

        $header = $this->getHeader();
        // set position to 12
        $this->offset = strlen($header->getMessage());
        $qdCount = $header->getQDCount();
        $this->questions = [];
        if ($qdCount < 1) {
            // set temp false
            return $this->questions;
        }
        $message = $this->getMessage()->getMessage();
        if (strlen($message) < ($this->offset + 5)) {
            return $this->questions;
        }

        while ($qdCount-- > 0) {
            $name = Buffer::readLabel($message, $this->offset);
            $type = $message[++$this->offset] ?? null;
            $this->offset++;
            $class = $message[++$this->offset] ?? null;
            $this->offset++;
            if (!is_string($type) || !is_string($class)) {
                throw new MalformedDataException(
                    'Response answer does not contain valid data'
                );
            }
            $this->questions[] = Question::fromFilteredResponse(
                $name,
                ord($type),
                ord($class)
            );
        }

        return $this->questions;
    }

    /**
     * Parse the records
     *
     * @return PacketResourceRecordsInterface
     * @throws MalformedDataException
     */
    private function parseRecords() : PacketResourceRecordsInterface
    {
        if (isset($this->records)) {
            return $this->records;
        }

        // do not process if truncated
        $this->records = new Records();
        $header = $this->getHeader();
        // if truncated
        if ($header->getTC() > 0) {
            return $this->records;
        }

        // if no question -> no process
        if ($this->getQuestion() === null) {
            return $this->records;
        }
        if (!isset(self::$namespace)) {
            $namespace = explode('\\', RRDefault::class);
            array_pop($namespace);
            $namespace = implode('\\', $namespace);
            self::$namespace = $namespace."\\";
        }

        $message = $this->getMessage();
        $data = $message->getMessage();
        $dataLength = strlen($data);
        // counts the answers
        $count = $header->getAnCount() // answer
            + $header->getNSCount()  // authority
            + $header->getARCount(); // additional
        for ($iteration = 0; $count > $iteration; $iteration++) {
            // stop if position greater than length
            if ($this->offset > $dataLength) {
                break;
            }
            $firstOffset = $this->offset;
            // skip the name
            while (($length = ord(substr($data, ($this->offset++), 1))) > 0) {
                if ($length > 63) {
                    $this->offset++;
                    break;
                }
                $this->offset += $length;
            }
            // read header
            $header = Buffer::read($data, $this->offset, 10);
            $headers  = strlen($header) !== 10 ? null : unpack('ntype/nb/Nc/nlength', $header);
            if (!$headers) {
                throw new MalformedDataException(
                    sprintf(
                        'Malformed data in offset %s : No Header',
                        $firstOffset
                    )
                );
            }
            try {
                $type = Lookup::resourceType($headers['type']);
            } catch (Throwable $e) {
                throw new MalformedDataException(
                    sprintf(
                        'Malformed data in offset %s: %s',
                        $firstOffset,
                        $e->getMessage()
                    )
                );
            }

            $this->offset += $headers['length'];
            $baseClass = self::$namespace . $type->getName();
            if (class_exists($baseClass) && is_subclass_of($baseClass, ResourceRecordTypeInterface::class)) {
                $this->records->add(new $baseClass($message, $firstOffset));
                continue;
            }
            $this->records->add(new RRDefault($message, $firstOffset));
        }

        // ANCOUNT + NSCOUNT + ARCOUNT === count(records)
        if (count($this->records) < $count) {
            throw new MalformedDataException(
                'Response answer is mismatch'
            );
        }

        return $this->records;
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
    public function getHeader(): PacketHeaderInterface
    {
        return $this->header??$this->parseHeader();
    }

    /**
     * @inheritdoc
     * @throws MalformedDataException
     */
    public function getQuestion(): ?PacketQuestionInterface
    {
        return $this->getQuestions()[0]??null;
    }

    /**
     * @throws MalformedDataException
     */
    public function getQuestions(): array
    {
        return $this->questions??$this->parseQuestions();
    }


    /**
     * @inheritdoc
     *
     * @throws MalformedDataException
     */
    public function getRecords(): PacketResourceRecordsInterface
    {
        return $this->records??$this->parseRecords();
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
     * @return array{message: PacketMessageInterface}
     */
    public function __serialize(): array
    {
        return ['message' => $this->message];
    }

    /**
     * Magic method for unserialize
     *
     * @param array{message: PacketMessageInterface} $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->message = $data['message'];
    }
}
