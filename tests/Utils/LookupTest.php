<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord\Utils;

use ArrayAccess\DnsRecord\DnsServer\CustomDnsServer;
use ArrayAccess\DnsRecord\DnsServer\Google;
use ArrayAccess\DnsRecord\DnsServerStorage;
use ArrayAccess\DnsRecord\Exceptions\EmptyArgumentException;
use ArrayAccess\DnsRecord\Exceptions\InvalidArgumentException;
use ArrayAccess\DnsRecord\Exceptions\RequestException;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\DSO;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\IQuery;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Notify;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Query;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Status;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Update;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\Any;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\IN;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QType;
use ArrayAccess\DnsRecord\Utils\Lookup;
use PHPUnit\Framework\TestCase;
use Throwable;
use function fclose;
use function is_resource;
use function sprintf;

class LookupTest extends TestCase
{

    public function testResourceClass()
    {
        $exception = null;
        try {
            // empty
            Lookup::resourceClass('');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(
            EmptyArgumentException::class,
            $exception,
            sprintf(
                '%1$s::Lookup::resourceClass() empty argument should throw %2$s',
                Lookup::class,
                EmptyArgumentException::class
            )
        );
        $exception = null;
        try {
            // invalid
            Lookup::resourceClass(0xffffff);
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(
            InvalidArgumentException::class,
            $exception,
            sprintf(
                '%1$s::resourceClass() out of range integer argument should throw %2$s',
                Lookup::class,
                InvalidArgumentException::class
            )
        );

        $this->assertInstanceOf(
            Any::class,
            Lookup::resourceClass('*'),
            sprintf(
                '%1$s::resourceClass("*") should return object instance of %2$s',
                Lookup::class,
                Any::class
            )
        );
        $this->assertInstanceOf(
            IN::class,
            Lookup::resourceClass(Lookup::QCLASS_IN),
            sprintf(
                '%1$s::resourceClass(%1$s::QCLASS_IN) should return object instance of %2$s',
                Lookup::class,
                IN::class
            )
        );
    }

    public function testResourceOpcode()
    {
        $exception = null;
        try {
            // empty
            Lookup::resourceOpcode('');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(
            EmptyArgumentException::class,
            $exception,
            sprintf(
                '%1$s::resourceOpcode() empty argument should throw %2$s',
                Lookup::class,
                EmptyArgumentException::class
            )
        );
        try {
            // empty
            Lookup::resourceOpcode('INVALID');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(
            InvalidArgumentException::class,
            $exception,
            sprintf(
                '%1$s::resourceOpcode() invalid argument should throw %2$s',
                Lookup::class,
                InvalidArgumentException::class
            )
        );
        $this->assertInstanceOf(
            \InvalidArgumentException::class,
            $exception,
            sprintf(
                '%1$s::resourceOpcode() invalid argument should throw %2$s',
                Lookup::class,
                \InvalidArgumentException::class
            )
        );
        $this->assertInstanceOf(
            Query::class,
            Lookup::resourceOpcode(Lookup::OPCODE_QUERY),
            sprintf(
                '%1$s::resourceOpcode(%1$s::OPCODE_QUERY) should return object instance of %2$s',
                Lookup::class,
                Query::class
            )
        );
        $this->assertInstanceOf(
            IQuery::class,
            Lookup::resourceOpcode(Lookup::OPCODE_IQUERY),
            sprintf(
                '%1$s::resourceOpcode(%1$s::OPCODE_IQUERY) should return object instance of %2$s',
                Lookup::class,
                IQuery::class
            )
        );
        $this->assertInstanceOf(
            Status::class,
            Lookup::resourceOpcode(Lookup::OPCODE_STATUS),
            sprintf(
                '%1$s::resourceOpcode(%1$s::OPCODE_STATUS) should return object instance of %2$s',
                Lookup::class,
                Status::class
            )
        );
        $this->assertInstanceOf(
            DSO::class,
            Lookup::resourceOpcode(Lookup::OPCODE_DSO),
            sprintf(
                '%1$s::resourceOpcode(%1$s::OPCODE_DSO) should return object instance of %2$s',
                Lookup::class,
                DSO::class
            )
        );
        $this->assertInstanceOf(
            Notify::class,
            Lookup::resourceOpcode(Lookup::OPCODE_NOTIFY),
            sprintf(
                '%1$s::resourceOpcode(%1$s::OPCODE_NOTIFY) should return object instance of %2$s',
                Lookup::class,
                Notify::class
            )
        );
        $this->assertInstanceOf(
            Update::class,
            Lookup::resourceOpcode(Lookup::OPCODE_UPDATE),
            sprintf(
                '%1$s::resourceOpcode(%1$s::OPCODE_UPDATE) should return object instance of %2$s',
                Lookup::class,
                Update::class
            )
        );
    }

    public function testResourceType()
    {
        $exception = null;
        try {
            // empty
            Lookup::resourceType('');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(
            EmptyArgumentException::class,
            $exception,
            sprintf(
                '%1$s::resourceType() empty argument should throw %2$s',
                Lookup::class,
                EmptyArgumentException::class
            )
        );
        try {
            // empty
            Lookup::resourceType('INVALID');
        } catch (Throwable $exception) {
        }
        $this->assertInstanceOf(
            InvalidArgumentException::class,
            $exception,
            sprintf(
                '%1$s::resourceType() invalid argument should throw %2$s',
                Lookup::class,
                InvalidArgumentException::class
            )
        );
        /**
         * @uses QType no need to test QType again
         */
        $this->assertInstanceOf(
            QType::class,
            Lookup::resourceType('A'),
            sprintf(
                '%1$s::resourceType("A") should return object instance of %2$s',
                Lookup::class,
                QType::class
            )
        );
        $this->assertSame(
            'A',
            Lookup::resourceType('A')->getName(),
            sprintf(
                '%1$s::resourceType("A")->getName() should "A"',
                Lookup::class
            )
        );
    }

    public function testSocket()
    {
        /*
         * protocol:string, server:string, port:int, socket: resource
         */
        // use only one!
        $dnsServer = new DnsServerStorage(new Google());
        try {
            $socketDefinition = Lookup::socket(
                $dnsServer,
                true, // use udp,
                $serverLists
            );
            $this->assertIsArray(
                $serverLists,
                sprintf(
                    '%s::socket(a, v, $serverLists) - $serverLists should reference as array',
                    Lookup::class
                )
            );
            $this->assertArrayHasKey(
                'protocol',
                $socketDefinition,
                sprintf(
                    '%s::socket() result array should contain key %s',
                    Lookup::class,
                    'protocol'
                )
            );
            $this->assertArrayHasKey(
                'server',
                $socketDefinition,
                sprintf(
                    '%s::socket() result array should contain key %s',
                    Lookup::class,
                    'server'
                )
            );
            $this->assertArrayHasKey(
                'port',
                $socketDefinition,
                sprintf(
                    '%s::socket() result array should contain key %s',
                    Lookup::class,
                    'port'
                )
            );
            $this->assertArrayHasKey(
                'socket',
                $socketDefinition,
                sprintf(
                    '%s::socket() result array should contain key %s',
                    Lookup::class,
                    'socket'
                )
            );
            $this->assertIsString(
                $socketDefinition['protocol'],
                sprintf(
                    '%s::socket()[\'protocol\'] should returning string',
                    Lookup::class,
                )
            );
            $this->assertSame(
                $socketDefinition['protocol'],
                'udp',
                sprintf(
                    '%s::socket()[\'protocol\'] should "udp"',
                    Lookup::class,
                )
            );
            $this->assertIsString(
                $socketDefinition['server'],
                sprintf(
                    '%s::socket()[\'server\'] should returning string',
                    Lookup::class,
                )
            );
            $this->assertIsInt(
                $socketDefinition['port'],
                sprintf(
                    '%s::socket()[\'port\'] should returning int',
                    Lookup::class,
                )
            );
            $this->assertIsBool(
                is_resource($socketDefinition['socket']),
                sprintf(
                    '%s::socket()[\'socket\'] should returning resource',
                    Lookup::class,
                )
            );
            fclose($socketDefinition['socket']);
            unset($socketDefinition['socket']);
        } catch (Throwable) {
        }
        $dnsServer = new DnsServerStorage(CustomDnsServer::create('https://invalid-ns'));
        try {
            Lookup::socket(
                $dnsServer,
                true, // use udp,
                $serverLists
            );
        } catch (Throwable $e) {
            $this->assertInstanceOf(
                RequestException::class,
                $e,
                sprintf(
                    '%1$s::socket() throwable should return object instance of %2$s',
                    Lookup::class,
                    RequestException::class
                )
            );
        }
    }
}
