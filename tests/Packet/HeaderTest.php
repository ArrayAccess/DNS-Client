<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Exceptions\LengthException;
use ArrayAccess\DnsRecord\Packet\Header;
use PHPUnit\Framework\TestCase;
use Throwable;
use function sprintf;

class HeaderTest extends TestCase
{
    /**
     * Just test header exception
     *
     * @see \Tests\ArrayAccess\DnsRecord\Utils\BufferTest::testCreateQuestionMessage()
     */
    public function testFromMessage()
    {
        try {
            Header::fromMessage('');
        } catch (Throwable $e) {
            $this->assertInstanceOf(
                LengthException::class,
                $e,
                sprintf(
                    '%1$s::fromMessage("") should throw %2$s',
                    Header::class,
                    LengthException::class
                )
            );
        }
    }
}
