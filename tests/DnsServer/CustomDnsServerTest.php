<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\DnsServer\CustomDnsServer;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerInterface;
use PHPUnit\Framework\TestCase;
use function sprintf;

class CustomDnsServerTest extends TestCase
{

    public function testConstruct()
    {
        $this->assertInstanceOf(
            DnsServerInterface::class,
            new CustomDnsServer('8.8.8.8'),
            sprintf(
                'Object %1$s should instanceof %2$s',
                CustomDnsServer::class,
                DnsServerInterface::class
            )
        );
    }

    public function testCreate()
    {
        $dns = '8.8.8.8';
        $customDns = CustomDnsServer::create($dns);
        $this->assertSame(
            $customDns::class,
            CustomDnsServer::class,
            sprintf(
                '%1$s::create("%2$s") should use object class of %1$s',
                CustomDnsServer::class,
                $dns
            )
        );

        $this->assertSame(
            $customDns->getPrimaryServer(),
            '8.8.8.8',
            sprintf(
                'Object %1$s->getPrimaryServer() should %2$s',
                CustomDnsServer::class,
                $dns
            )
        );

        $this->assertNull(
            $customDns->getSecondaryServer(),
            sprintf(
                'Object %1$s->getSecondaryServer() should null if secondary not declared',
                CustomDnsServer::class
            )
        );

        $this->assertSame(
            $customDns->getPort(),
            53,
            sprintf(
                'Object %1$s->getPort() should integer 53 as default port',
                CustomDnsServer::class
            )
        );
    }
}
