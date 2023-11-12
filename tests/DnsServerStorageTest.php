<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord;

use ArrayAccess\DnsRecord\DnsServer\CustomDnsServer;
use ArrayAccess\DnsRecord\DnsServer\Google;
use ArrayAccess\DnsRecord\DnsServerStorage;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerInterface;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;
use Serializable;
use Throwable;
use function reset;
use function restore_error_handler;
use function serialize;
use function sprintf;
use function unserialize;

class DnsServerStorageTest extends TestCase
{
    protected DnsServerStorage $dnsServerStorage;

    /**
     * @var class-string<DnsServerStorage>
     */
    protected string $dnsClassName;

    /**
     * @inheritdoc
     *
     * Create new object each test method called
     */
    public function setUp(): void
    {
        $this->dnsServerStorage = DnsServerStorage::createDefault();
        $this->dnsClassName = $this->dnsServerStorage::class;
    }

    public function testObject() : void
    {
        $google = new Google();
        $this->assertContains(
            $google,
            (new DnsServerStorage($google))->getServers(),
            sprintf(
                '%1$s->getServers() should contain new object of %2$s',
                DnsServerStorage::class,
                $google::class
            )
        );
        $this->assertInstanceOf(
            Serializable::class,
            $this->dnsServerStorage,
            sprintf(
                'Object %1$s should instanceof %2$s',
                $this->dnsClassName,
                Serializable::class
            )
        );
        $this->assertInstanceOf(
            Countable::class,
            $this->dnsServerStorage,
            sprintf(
                'Object %1$s should instanceof %2$s',
                $this->dnsClassName,
                Countable::class
            )
        );
        $this->assertInstanceOf(
            IteratorAggregate::class,
            $this->dnsServerStorage,
            sprintf(
                'Object %1$s should instanceof %2$s',
                $this->dnsClassName,
                IteratorAggregate::class
            )
        );
    }

    public function testGetServers() : void
    {
        $this->assertNotEmpty(
            $this->dnsServerStorage->getServers(),
            sprintf(
                '%1$s->getServers() should not empty',
                $this->dnsClassName
            )
        );
    }

    public function testCount() : void
    {
        $this->assertCount(
            count($this->dnsServerStorage->getServers()),
            $this->dnsServerStorage,
            sprintf(
                'count(%1$s->getServers()) && count(%1$s) should equal',
                $this->dnsClassName
            )
        );
    }

    public function testGetIterator() : void
    {
        $this->assertInstanceOf(
            ArrayIterator::class,
            $this->dnsServerStorage->getIterator(),
            sprintf(
                '%1$s->getIterator() should instance if %2$s',
                $this->dnsClassName,
                ArrayIterator::class
            )
        );

        foreach ($this->dnsServerStorage as $key => $server) {
            $this->assertIsString(
                $key,
                sprintf(
                    'Looping if %1$s->getIterator() key should string',
                    $this->dnsClassName
                )
            );
            $this->assertInstanceOf(
                DnsServerInterface::class,
                $server,
                sprintf(
                    'Looping if %1$s->getIterator() value should instance of %2$s',
                    $this->dnsClassName,
                    DnsServerInterface::class
                )
            );
        }
    }

    public function testRemove() : void
    {
        $servers = $this->dnsServerStorage->getServers();
        $total = count($servers);
        /**
         * @uses DnsServerStorage::remove() should no-return
         * @noinspection PhpVoidFunctionResultUsedInspection
         */
        $this->assertNull(
            $this->dnsServerStorage->remove(reset($servers)), // @phpstan-ignore-line
            sprintf(
                '%s->remove() should no return',
                $this->dnsClassName
            )
        );
        $this->assertGreaterThan(
            count($this->dnsServerStorage),
            $total,
            sprintf(
                'count(%1$s->getServers()) should greater count(%1$s)',
                $this->dnsClassName
            )
        );
        $this->assertSame(
            count($this->dnsServerStorage),
            $this->dnsServerStorage->count(),
            sprintf(
                'count(%1$s) should identical with %1$s->count()',
                $this->dnsClassName
            )
        );
    }

    public function testGet() : void
    {
        $this->assertNull(
            $this->dnsServerStorage->get('not_exist_server'),
            'Dns server identity "not_exist_server" should not exist'
        );
        /**
         * @var Google $google
         */
        $google = $this->dnsServerStorage::getDefaultServer(Google::class);
        $this->assertSame(
            $this->dnsServerStorage->get($google->getIdentity()),
            $google,
            sprintf(
                '%1$s->get(%2$s) should equal with %1$s::getDefaultServer(%3$s)',
                $this->dnsClassName,
                $google->getIdentity(),
                Google::class
            )
        );
        $this->assertSame(
            $this->dnsServerStorage->get($google),
            $google,
            sprintf(
                'Method %1$s->get(%2$s) should equal with object %3$s',
                $this->dnsClassName,
                $google::class,
                Google::class
            )
        );
        $this->assertSame(
            $this->dnsServerStorage->get($google)?->getIdentity(),
            $google::class,
            sprintf(
                'Method %1$s->get(%2$s)->getIdentity() should equal with id %3$s',
                $this->dnsClassName,
                $google::class,
                Google::class
            )
        );
    }

    public function testAdd() : void
    {
        $total = count($this->dnsServerStorage);

        $custom = CustomDnsServer::create('localhost');
        /**
         * @uses DnsServerStorage::add() should no-return
         * @noinspection PhpVoidFunctionResultUsedInspection
         */
        $this->assertNull(
            $this->dnsServerStorage->add($custom), // @phpstan-ignore-line
            sprintf(
                '%1$s->add(%2$s) should no return',
                $this->dnsClassName,
                $custom::class
            )
        );
        $this->assertGreaterThan(
            $total,
            count($this->dnsServerStorage->getServers()),
            sprintf(
                'count(%1$s->getServers()) should greater than previous count(%1$s)',
                $this->dnsClassName
            )
        );
        $this->assertCount(
            $total + 1,
            $this->dnsServerStorage,
            sprintf(
                'count(%1$s->getServers()) should equal than previous count(%1$s) + 1',
                $this->dnsClassName
            )
        );
        $this->assertContains(
            $custom,
            $this->dnsServerStorage->getServers(),
            sprintf(
                '%1$s->getServers() should contain %2$s<"localhost">',
                $this->dnsClassName,
                $custom::class
            )
        );
        $this->assertArrayHasKey(
            $custom->getIdentity(),
            $this->dnsServerStorage->getServers(),
            sprintf(
                '%1$s->getServers() should contain key %2$s->getIdentity()',
                $this->dnsClassName,
                $custom::class
            )
        );
        $this->assertSame(
            $this->dnsServerStorage->get($custom->getIdentity()),
            $custom,
            sprintf(
                '%1$s->get(%2$s) should equal with %2$s<"localhost">',
                $this->dnsClassName,
                $custom::class
            )
        );
    }

    public function testGetDefaultServer() : void
    {
        $this->assertInstanceOf(
            Google::class,
            $this->dnsServerStorage::getDefaultServer(Google::class),
            sprintf(
                '%1$s::getDefaultServer(%2$s) should instance of %2$s',
                $this->dnsClassName,
                Google::class
            )
        );
        $this->assertSame(
            // get static object stored
            $this->dnsServerStorage::getDefaultServer(Google::class),
            $this->dnsServerStorage->get(Google::class),
            sprintf(
                '%1$s::getDefaultServer(%2$s) should equal with %1$s->get(%2$s)',
                $this->dnsClassName,
                Google::class
            )
        );
    }

    public function testCreateDefault() : void
    {
        $this->assertInstanceOf(
            $this->dnsClassName,
            DnsServerStorage::createDefault(),
            sprintf(
                '%1$s::createDefault() should instanceof %2$s',
                DnsServerStorage::class,
                $this->dnsClassName
            )
        );
        // default is identical data
        $this->assertSame(
            DnsServerStorage::createDefault()->getServers(),
            $this->dnsServerStorage->getServers(),
            sprintf(
                '%1$s::createDefault()->getServers() should equal with default %2$s->getServers()',
                DnsServerStorage::class,
                $this->dnsClassName
            )
        );

        $this->assertNotSame(
            DnsServerStorage::createDefault(),
            $this->dnsServerStorage,
            sprintf(
                '%1$s::createDefault() should create new object of %1$s',
                DnsServerStorage::class
            )
        );
    }

    public function testSerialize() : void
    {
        try {
            // just reduce error
            $serialize = serialize($this->dnsServerStorage);
        } catch (Throwable) {
            $serialize = null;
        }
        restore_error_handler();
        $this->assertIsString(
            $serialize,
            sprintf(
                'serialize(%s) should string',
                $this->dnsClassName
            )
        );
        $this->assertStringStartsWith(
            'O:', // O:[0-9]+:"className:..."
            $serialize,
            sprintf(
                'serialize(%s) should serialized object string',
                $this->dnsClassName
            )
        );
        // serialize data should be identical
        $this->assertSame(
            $serialize,
            serialize($this->dnsServerStorage),
            sprintf(
                'serialize(%1$s) should equal with next serialize(%1$s)',
                $this->dnsClassName
            )
        );
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertIsString(
            $this->dnsServerStorage->serialize(),
            sprintf(
                '%1$s->serialize() should string',
                $this->dnsClassName
            )
        );
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertArrayHasKey(
            'servers',
            unserialize($this->dnsServerStorage->serialize()),
            sprintf(
                'unserialize(%1$s->serialize()) should array has key "servers"',
                $this->dnsClassName
            )
        );
    }

    public function testUnserialize() : void
    {
        try {
            $object = unserialize(serialize($this->dnsServerStorage));
        } catch (Throwable) {
            $object = null;
        }
        $this->assertInstanceOf(
            $this->dnsClassName,
            $object,
            sprintf(
                'unserialize(serialize(%1$s)) should instanceof %1$s',
                $this->dnsClassName
            )
        );
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertNull(
            $this->dnsServerStorage->unserialize(  // @phpstan-ignore-line
                $this->dnsServerStorage->serialize()
            ),
            sprintf(
                '%1$s->unserialize(%1$s->serialize()) should no return',
                $this->dnsClassName
            )
        );
    }
}
