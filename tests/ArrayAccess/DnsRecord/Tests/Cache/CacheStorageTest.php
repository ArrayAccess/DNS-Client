<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Tests\Cache;

use ArrayAccess\DnsRecord\Cache\Adapter\ArrayCacheAdapter;
use ArrayAccess\DnsRecord\Cache\CacheStorage;
use ArrayAccess\DnsRecord\DnsServerStorage;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketHeaderInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketQuestionInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResourceRecordsInterface;
use ArrayAccess\DnsRecord\Packet\Answers;
use ArrayAccess\DnsRecord\Packet\Header;
use ArrayAccess\DnsRecord\Packet\Question;
use ArrayAccess\DnsRecord\Packet\Records;
use ArrayAccess\DnsRecord\Packet\Request;
use ArrayAccess\DnsRecord\Packet\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function microtime;
use function sprintf;

class CacheStorageTest extends TestCase
{
    protected CacheStorage $cacheStorage;

    protected string $cacheStorageClassName;

    protected function setUp(): void
    {
        $this->cacheStorage = new CacheStorage();
        $this->cacheStorageClassName = $this->cacheStorage::class;
    }

    private function createFakePacketRequestData() : PacketRequestDataInterface
    {
        return new class implements PacketRequestDataInterface
        {
            public function serialize()
            {
            }

            public function unserialize(string $data)
            {
            }

            public function getHeader(): PacketHeaderInterface
            {
                return (new ReflectionClass(Header::class))->newInstanceWithoutConstructor();
            }

            public function withHeader(PacketHeaderInterface $header): PacketRequestDataInterface
            {
                return new self();
            }

            public function getQuestions(): array
            {
                return [];
            }

            public function getQuestion(): PacketQuestionInterface
            {
                return (new ReflectionClass(Question::class))->newInstanceWithoutConstructor();
            }

            public function getAdditionalRecords(): PacketResourceRecordsInterface
            {
                return new Records();
            }

            public function getAuthorityRecords(): PacketResourceRecordsInterface
            {
                return new Records();
            }

            public function getAnswerRecords(): PacketResourceRecordsInterface
            {
                return new Records();
            }

            public function getDnsServerStorage(): DnsServerStorageInterface
            {
                // fake is default
                return DnsServerStorage::createDefault();
            }

            public function getQueryMessage(): string
            {
                return '';// fake is empty
            }

            public function createRequest(?CacheStorageInterface $cacheStorage = null): PacketRequestInterface
            {
                return (new ReflectionClass(Request::class))->newInstanceWithoutConstructor();
            }

            public function __serialize(): array
            {
                return [];
            }

            public function __unserialize(array $data): void
            {
            }
        };
    }

    private function createFakeResponse(): Response
    {
        return new Response(
            microtime(true) * 1000,
            microtime(true) * 1000 + 20,
            'udp',
            '8.8.8.8',
            53,
            $this->createFakePacketRequestData(),
            new Answers('')
        );
    }

    public function testGetCacheName()
    {
        $cacheName = $this->cacheStorage->getCacheName(
            $this->createFakePacketRequestData()
        );
        $this->assertStringStartsWith(
            CacheStorage::PREFIX,
            $cacheName,
            sprintf(
                '%1$s->getCacheName(%2$s) should prefixed with %3$s',
                $this->cacheStorageClassName,
                PacketRequestDataInterface::class,
                CacheStorage::PREFIX
            )
        );
        $stringKey = 'cache';
        $this->assertSame(
            $stringKey,
            $stringKey,
            sprintf(
                '%1$s->getCacheName("%2$s") should identical with %2$s',
                $this->cacheStorageClassName,
                $stringKey
            )
        );
    }

    public function testGetAdapter()
    {
        $this->assertNull(
            $this->cacheStorage->getAdapter(),
            sprintf(
                '%1$s->getAdapter() should returning null if does not have adapter',
                $this->cacheStorageClassName
            )
        );
    }

    public function testSetAdapter()
    {
        $cacheAdapter = new ArrayCacheAdapter();
        /**
         * @uses CacheStorage::setAdapter() should no-return
         * @noinspection PhpVoidFunctionResultUsedInspection
         */
        $this->assertNull(
            $this->cacheStorage->setAdapter($cacheAdapter),
            sprintf(
                '%1$s->setAdapter(%2$s) should no return',
                $this->cacheStorageClassName,
                $cacheAdapter::class
            )
        );

        $this->assertSame(
            $this->cacheStorage->getAdapter(),
            $cacheAdapter,
            sprintf(
                '%1$s->getAdapter() should equal with current set adapter %2$s',
                $this->cacheStorageClassName,
                $cacheAdapter::class
            )
        );
    }

    public function testSaveItem()
    {
        $fakeResponse = $this->createFakeResponse();
        $this->assertFalse(
            $this->cacheStorage->saveItem($fakeResponse),
            sprintf(
                '%1$s->saveItem("%2$s") should returning false if does not adapter',
                $this->cacheStorageClassName,
                $fakeResponse::class
            )
        );
        $cacheAdapter = new ArrayCacheAdapter();
        $this->cacheStorage->setAdapter($cacheAdapter);
        $this->assertTrue(
            $this->cacheStorage->saveItem($fakeResponse),
            sprintf(
                '%1$s->saveItem("%2$s") should returning boolean true if has array adapter',
                $cacheAdapter::class,
                $fakeResponse::class,
            )
        );
    }

    public function testGetItem()
    {
        $key = 'nothing';
        $this->assertNull(
            $this->cacheStorage->getItem($key),
            sprintf(
                '%1$s->getItem("%2$s") should returning null if does not have adapter',
                $this->cacheStorageClassName,
                $key
            )
        );

        $cacheAdapter = new ArrayCacheAdapter();
        $this->cacheStorage->setAdapter($cacheAdapter);
        $this->assertNull(
            $this->cacheStorage->getItem($key),
            sprintf(
                '%1$s->getItem("%2$s") should returning null if does not have data',
                $this->cacheStorageClassName,
                $key
            )
        );
        $this->assertInstanceOf(
            CacheDataInterface::class,
            $cacheAdapter->getItem($key),
            sprintf(
                '%1$s->getItem("%2$s") should returning object instance of %3$s',
                $cacheAdapter::class,
                $key,
                CacheDataInterface::class,
            )
        );
        $fakeResponse = $this->createFakeResponse();
        $this->cacheStorage->saveItem($fakeResponse);
        $this->assertSame(
            $fakeResponse,
            $this->cacheStorage->getItem($fakeResponse->getPacketData()),
            sprintf(
                '%1$s->getItem("%2$s") should returning object instance of %3$s',
                $cacheAdapter::class,
                $key,
                $fakeResponse::class,
            )
        );
    }

    public function testDeleteItem()
    {
        $key = 'nothing';
        $this->assertFalse(
            $this->cacheStorage->deleteItem($key),
            sprintf(
                '%1$s->deleteItem("%2$s") should returning boolean false if no adapter',
                $this->cacheStorageClassName,
                $key
            )
        );
        $cacheAdapter = new ArrayCacheAdapter();
        $this->cacheStorage->setAdapter($cacheAdapter);
        $fakeResponse = $this->createFakeResponse();
        $this->cacheStorage->saveItem($fakeResponse);

        $this->assertTrue(
            $this->cacheStorage->deleteItem($key),
            sprintf(
                '%1$s->deleteItem("%2$s") should returning boolean true if has data',
                $this->cacheStorageClassName,
                $key
            )
        );
    }

    public function testDeleteItems()
    {
        $key = 'nothing';
        $this->assertFalse(
            $this->cacheStorage->deleteItems($key),
            sprintf(
                '%1$s->deleteItems("%2$s") should returning boolean false if no adapter',
                $this->cacheStorageClassName,
                $key
            )
        );
        $cacheAdapter = new ArrayCacheAdapter();
        $this->cacheStorage->setAdapter($cacheAdapter);
        $fakeResponse = $this->createFakeResponse();
        $this->cacheStorage->saveItem($fakeResponse);

        $this->assertTrue(
            $this->cacheStorage->deleteItems($key),
            sprintf(
                '%1$s->deleteItems("%2$s") should returning boolean true if has data',
                $this->cacheStorageClassName,
                $key
            )
        );
    }

    public function testHasItem()
    {
        $fakeResponse = $this->createFakeResponse();
        $this->assertFalse(
            $this->cacheStorage->hasItem($fakeResponse->getPacketData()),
            sprintf(
                '%1$s->hasItem(%2$s) should returning boolean false if no adapter',
                $this->cacheStorageClassName,
                $fakeResponse::class
            )
        );
        $cacheAdapter = new ArrayCacheAdapter();
        $this->cacheStorage->setAdapter($cacheAdapter);
        $this->cacheStorage->saveItem($fakeResponse);

        $this->assertTrue(
            $this->cacheStorage->hasItem($fakeResponse->getPacketData()),
            sprintf(
                '%1$s->hasItem(%2$s->getPacketData()) should returning boolean true if has data',
                $this->cacheStorageClassName,
                $fakeResponse::class
            )
        );
    }
}
