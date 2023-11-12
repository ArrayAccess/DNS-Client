<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord\Utils;

use ArrayAccess\DnsRecord\Packet\Header;
use ArrayAccess\DnsRecord\Packet\Question;
use ArrayAccess\DnsRecord\Utils\Buffer;
use ArrayAccess\DnsRecord\Utils\Lookup;
use PHPUnit\Framework\TestCase;
use function sprintf;
use function strlen;

class BufferTest extends TestCase
{

    public function testRead() : void
    {
        $printed = 'printed';
        $skipped = '_skipped';
        $buffer = $printed.$skipped;
        $readLength = 7;
        $offset = 0;
        $read = Buffer::read($buffer, $offset, $readLength);
        $this->assertSame(
            $printed,
            $read,
            sprintf(
                '%s::read("%2$s", $offset, 7) should return "%3$s"',
                Buffer::class,
                $buffer,
                $printed
            )
        );
        $this->assertSame(
            $readLength,
            $offset,
            sprintf(
                'Offset from %s::read("%2$s", $offset, 7) should %3$d',
                Buffer::class,
                $buffer,
                $readLength
            )
        );
        // offset maximum should strlen($buffer)
        $read = Buffer::read($buffer, $offset, 1000);
        $this->assertSame(
            $skipped,
            $read,
            sprintf(
                '%s::read("%2$s", $offset, 1000) should return "%3$s"',
                Buffer::class,
                $buffer,
                $skipped
            )
        );
        $this->assertSame(
            strlen($buffer),
            $offset,
            sprintf(
                'Offset from %s::read("%2$s", $offset, 1000) should %3$d',
                Buffer::class,
                $buffer,
                strlen($buffer)
            )
        );
    }

    public function testCreateHeaderMessage() : void
    {
        $header = Header::createQueryHeader();
        $this->assertSame(
            $header->getMessage(),
            Buffer::createHeaderMessage($header),
            sprintf(
                '%1$s::createHeaderMessage($header) should identical with $header->getMessage()',
                Buffer::class
            )
        );
    }

    public function testCompressLabel() : void
    {
        $domain = 'example.com';
        $label = Buffer::compressLabel($domain);
        $this->assertNotSame(
            $domain,
            $label,
            sprintf(
                '%1$s::compressLabel("%2$s") should not identical with "%2$s"',
                Buffer::class,
                $domain
            )
        );
    }

    public function testReadLabel() : void
    {
        $offset = 0;
        $domain = 'example.com';
        $label = Buffer::compressLabel($domain);
        $read = Buffer::readLabel($label, $offset);
        $this->assertGreaterThan(
            0,
            $offset,
            sprintf(
                'Offset from %1$s::readLabel($label, $offset) should greater than 0',
                Buffer::class
            )
        );
        $this->assertNotSame(
            $label,
            $read,
            sprintf(
                '%1$s::readLabel($label, $offset) should not identical with $label',
                Buffer::class
            )
        );
        $this->assertSame(
            $domain,
            $read,
            sprintf(
                '%1$s::readLabel($label, $offset) should identical with $label',
                Buffer::class
            )
        );
        $this->assertEmpty(
            Buffer::readLabel($label, $offset),
            sprintf(
                'With next offset of %1$s::readLabel($label, $offset) should empty',
                Buffer::class
            )
        );
        $underscore = 'example_com'; // underscore separator
        // fallback offset
        $offset = 0;
        $read = Buffer::readLabel($label, $offset, '_');
        $this->assertGreaterThan(
            0,
            $offset,
            sprintf(
                'Offset from %1$s::readLabel($label, $offset, "_") should greater than 0',
                Buffer::class
            )
        );
        $this->assertNotSame(
            $label,
            $read,
            sprintf(
                '%1$s::readLabel($label, $offset, "_") should not identical with $label',
                Buffer::class
            )
        );
        $this->assertNotSame(
            $domain,
            $read,
            sprintf(
                '%1$s::readLabel($label, $offset, "_") should not identical with "%2$s"',
                Buffer::class,
                $domain
            )
        );
        $this->assertSame(
            $underscore,
            $read,
            sprintf(
                '%1$s::readLabel($label, $offset) should identical with "%2$s"',
                Buffer::class,
                $underscore
            )
        );
    }

    public function testCompressHeader() : void
    {
        $header = Buffer::compressHeader(
            'A',
            'IN'
        );
        $this->assertGreaterThanOrEqual(
            10,
            strlen($header),
            sprintf(
                '%1$s::compressHeader("A", "IN") length should 10 or greater',
                Buffer::class
            )
        );
    }

    public function testCreateQuestionMessage() : void
    {
        $question = Question::fromFilteredResponse(
            'example.com',
            Lookup::RR_TYPES['A'],
            Lookup::QCLASS_IN
        );
        /**
         * @var non-empty-string $header
         */
        $header = Buffer::compressHeader(
            $question->getType(), // @phpstan-ignore-line
            $question->getClass() // @phpstan-ignore-line
        );
        $message = Buffer::createQuestionMessage($question);
        // header is on the end of binary string
        $this->assertStringEndsWith(
            $header,
            $message,
            sprintf(
                '%1$s::createQuestionMessage(%2$s) should ending with header',
                Buffer::class,
                Question::class,
            )
        );
    }
}
