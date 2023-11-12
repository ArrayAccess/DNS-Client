<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord\Packet;

use ArrayAccess\DnsRecord\Packet\Message;
use PHPUnit\Framework\TestCase;
use function md5;
use function microtime;
use function sprintf;

class MessageTest extends TestCase
{
    public function testToString() : void
    {
        $message = md5(microtime());
        $this->assertSame(
            (string) new Message($message),
            $message,
            sprintf(
                '%s->__tostring() should identical "%2$s"',
                Message::class,
                $message
            )
        );
    }

    public function testGetMessage() : void
    {
        $message = md5(microtime());
        $this->assertSame(
            (new Message($message))->getMessage(),
            $message,
            sprintf(
                '%s->getMessage() should identical "%2$s"',
                Message::class,
                $message
            )
        );
    }
}
