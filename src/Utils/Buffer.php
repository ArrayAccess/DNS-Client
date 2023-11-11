<?php
/** @noinspection PhpComposerExtensionStubsInspection */
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Utils;

use ArrayAccess\DnsRecord\Exceptions\EmptyArgumentException;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketQuestionInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordClassInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQTypeDefinitionInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use ArrayAccess\DnsRecord\Packet\Header;
use ArrayAccess\DnsRecord\ResourceRecord\RRTypes\OPT;
use function chr;
use function explode;
use function implode;
use function ord;
use function pack;
use function preg_replace;
use function strlen;
use function strtolower;
use function substr;
use function trim;

class Buffer
{
    /**
     * Read buffer offset
     *
     * @param string $buffer
     * @param int $length
     * @param int $offset
     * @return string
     */
    public static function read(string $buffer, int &$offset, int $length): string
    {
        $out = substr($buffer, $offset, $length);
        $offset += $length;
        if ($offset > ($max = strlen($buffer))) {
            $offset = $max;
        }
        return $out;
    }

    /**
     * Read Name By raw data
     *
     * @param string $buffer
     * @param int $offset
     * @param string $delimiter
     * @return string
     */

    public static function readLabel(string $buffer, int &$offset, string $delimiter = '.'): string
    {
        $bufferLength = strlen($buffer);
        if ($bufferLength <= $offset) {
            return '';
        }
        $out = [];
        while (true) {
            if ($bufferLength <= $offset) {
                break;
            }
            $length = ord($buffer[$offset]);
            if ($length === 0) {
                ++$offset;
                break;
            } elseif (($length & 0xc0) === 0xc0) {
                $pointer = ord($buffer[$offset]) << 8 | ord($buffer[$offset+1]);
                $pointer = $pointer & 0x3fff;
                $name2 = self::readLabel($buffer, $pointer);
                $out[] = $name2;
                $offset += 2;
                break;
            } else {
                ++$offset;
                $elem = substr($buffer, $offset, $length);
                $out[] = $elem;
                $offset += $length;
            }
        }
        return implode($delimiter, $out);
    }

    /**
     * Create 12-bit data from given header
     *
     * @see Header
     * @link https://datatracker.ietf.org/doc/html/rfc5395#section-2
     * @param Header $header
     *
     * @return string
     */
    public static function createHeaderMessage(Header $header) : string
    {
        $message  = pack('n', $header->getId());
        $message .= chr(
            ($header->getQR() << 7)
            | ($header->getOpCode()->getValue() << 3)
            | ($header->getAA() << 2)
            | ($header->getTC() << 1)
            | ($header->getRD())
        );
        $message .= chr(
            ($header->getRA() << 7)
            | ($header->getAD() << 5)
            | ($header->getCD() << 4)
            | $header->getRCode()
        );
        $message .= pack(
            'n4',
            $header->getQDCount(),
            $header->getAnCount(),
            $header->getNSCount(),
            $header->getARCount()
        );
        return $message;
    }

    /**
     * Create a Question message (RData for query)
     *
     * @param PacketQuestionInterface $rr
     * @return string
     * @link https://datatracker.ietf.org/doc/html/rfc1035#section-4.1.2
     *
     * @see Question
     */
    public static function createQuestionMessage(PacketQuestionInterface $rr): string
    {
        $message  = self::compressLabel($rr->getName());
        $message .= self::compressHeader($rr->getType(), $rr->getClass());
        return $message;
    }

    /**
     * @param ResourceRecordQTypeDefinitionInterface|ResourceRecordTypeInterface|string|int $type
     * @param ResourceRecordClassInterface|string|int $class
     * @param int $ttl
     * @param int $rdLength
     * @return string
     */
    public static function compressHeader(
        ResourceRecordQTypeDefinitionInterface|ResourceRecordTypeInterface|string|int $type,
        ResourceRecordClassInterface|string|int $class,
        int $ttl = 0,
        int $rdLength = 0
    ): string {
        $type  = Lookup::resourceType($type);
        $class = Lookup::resourceClass($class);
        return pack(
            'nnNn',
            $type->getValue(),
            OPT::TYPE === $type->getName() ? $class->getName() : $class->getValue(),
            $ttl,
            $rdLength
        );
    }

    /**
     * Compress QNAME to readily send for QUERY
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1035#section-4.1.2
     * @param string $name
     * @return string
     */
    public static function compressLabel(string $name): string
    {
        $name = strtolower(trim($name));
        if (!$name) {
            throw new EmptyArgumentException(
                'Label could not be empty',
            );
        }

        $name = preg_replace('~\\\+.~', '.', $name);
        $computedName = '';
        foreach (explode('.', $name) as $label) {
            if ($label === '') {
                continue;
            }
            // truncate see RFC1035 2.3.1
            // https://datatracker.ietf.org/doc/html/rfc1035#section-2.3.4
            if (($length = strlen($label)) > 63) {
                $label = substr($label, 0, 63);
            }
            $computedName .= pack('C', $length);
            $computedName .= $label;
        }
        $computedName .= "\0";
        return $computedName;
    }
}
