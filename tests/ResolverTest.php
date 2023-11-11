<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord;

use ArrayAccess\DnsRecord\Cache\Adapter\ArrayCacheAdapter;
use ArrayAccess\DnsRecord\Cache\CacheStorage;
use ArrayAccess\DnsRecord\DnsServerStorage;
use ArrayAccess\DnsRecord\Exceptions\Exception;
use ArrayAccess\DnsRecord\Exceptions\RequestException;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResponseInterface;
use ArrayAccess\DnsRecord\Packet\Answers;
use ArrayAccess\DnsRecord\Resolver;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\IQuery;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Query;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\IN;
use ArrayAccess\DnsRecord\ResourceRecord\RRTypes\A;
use ArrayAccess\DnsRecord\ResourceRecord\RRTypes\OPT;
use PHPUnit\Framework\TestCase;
use Throwable;
use function sprintf;

/**
 * Many classes already handled by this method call
 * @see CacheStorageTest fot cache test
 */
class ResolverTest extends TestCase
{
    public function testSetDnsServerStorage()
    {
        $resolver = new Resolver();
        $dnsServerStorage = $resolver->getDnsServerStorage();
        $resolver->setDnsServerStorage(DnsServerStorage::createDefault());
        $this->assertNotSame(
            $dnsServerStorage,
            $resolver->getDnsServerStorage(),
            sprintf(
                '%1$s->getDnsServerStorage() should not identical with default dns storage',
                $resolver::class
            )
        );
    }

    public function testGetDnsServerStorage()
    {
        $resolver = new Resolver();
        $dnsServerStorage = $resolver->getDnsServerStorage();
        $this->assertInstanceOf(
            DnsServerStorage::class,
            $dnsServerStorage,
            sprintf(
                '%1$s->getDnsServerStorage() should instance of %2$s',
                $resolver::class,
                DnsServerStorage::class
            )
        );
        $resolver->setDnsServerStorage(DnsServerStorage::createDefault());
        $this->assertNotSame(
            $dnsServerStorage,
            $resolver->getDnsServerStorage(),
            sprintf(
                '%1$s->getDnsServerStorage() should not identical with default dns storage',
                $resolver::class
            )
        );
    }

    public function testSetCache()
    {
        $resolver = new Resolver();
        $cache = $resolver->getCache();
        $resolver->setCache(new CacheStorage());
        $this->assertNotSame(
            $cache,
            $resolver->getCache(),
            sprintf(
                '%1$s->getCache() should not identical with default cache',
                $resolver::class
            )
        );
    }

    public function testGetCache()
    {
        $resolver = new Resolver();
        $cache = $resolver->getCache();
        $this->assertInstanceOf(
            CacheStorageInterface::class,
            $cache,
            sprintf(
                '%1$s->getCache() should instance of %2$s',
                $resolver::class,
                CacheStorageInterface::class
            )
        );
        $this->assertNull(
            $cache->getAdapter(),
            sprintf(
                '%1$s->getCache()->getAdapter() should null if adapter not set',
                $resolver::class
            )
        );
        $arrayAdapter = new ArrayCacheAdapter();
        $newCache = new CacheStorage($arrayAdapter);
        $resolver->setCache($newCache);
        $this->assertNotSame(
            $cache,
            $resolver->getCache(),
            sprintf(
                '%1$s->getCache() should not identical with default cache',
                $resolver::class
            )
        );
        $this->assertSame(
            $arrayAdapter,
            $resolver->getCache()->getAdapter(),
            sprintf(
                '%1$s->getCache()->getAdapter() should identical with set adapter',
                $resolver::class
            )
        );
    }

    public function testQuery()
    {
        $resolver = new Resolver();
        $query = $resolver->query('example.com');
        $this->assertInstanceOf(
            PacketRequestDataInterface::class,
            $query,
            sprintf(
                '%1$s->query() should instance of %2$s',
                $resolver::class,
                PacketRequestDataInterface::class
            )
        );
        $this->assertInstanceOf(
            Query::class,
            $query->getHeader()->getOpCode(),
            sprintf(
                '%1$s->query()->getHeader()->getOpCode() should instance of %2$s',
                $resolver::class,
                Query::class
            )
        );
        // default use dnssec
        $this->assertNull(
            $query->getAdditionalRecords()->getFilteredType('OPT', true),
            'dnssec disable by default'
        );
        // set dns sec to true
        $resolver->setDnsSec(true);
        $query = $resolver->query('example.com');
        $this->assertInstanceOf(
            OPT::class,
            $query->getAdditionalRecords()->getFilteredType('OPT', true),
            'Enable dnssec and additional question contain OPT'
        );
    }

    public function testIQuery()
    {
        $resolver = new Resolver();
        $query = $resolver->iQuery('example.com');
        $this->assertInstanceOf(
            PacketRequestDataInterface::class,
            $query,
            sprintf(
                '%1$s->iQuery() should instance of %2$s',
                $resolver::class,
                PacketRequestDataInterface::class
            )
        );
        $this->assertInstanceOf(
            IQuery::class,
            $query->getHeader()->getOpCode(),
            sprintf(
                '%1$s->query()->getHeader()->getOpCode() should instance of %2$s',
                $resolver::class,
                IQuery::class
            )
        );
    }

    public function testLookup()
    {
        $resolver = new Resolver(
            DnsServerStorage::createDefault(),
            new CacheStorage(new ArrayCacheAdapter())
        );
        try {
            $lookup = $resolver->lookup(
                'example.com',
                timeout: 1
            );
        } catch (Throwable $e) {
            $this->assertInstanceOf(
                RequestException::class,
                $e
            );
            return;
        }

        $this->assertInstanceOf(
            PacketResponseInterface::class,
            $lookup,
            sprintf(
                '%1$s->lookup() should instanceof %2$s',
                $resolver::class,
                PacketResponseInterface::class
            )
        );
        // cause adapter is array, it should be identical
        $this->assertSame(
            $lookup,
            $resolver->lookup('example.com'),
            sprintf(
                '%1$s->lookup() should identical with current one',
                $resolver::class
            )
        );
    }

    public function testLookups()
    {
        $resolver = new Resolver();
        $lookups = $resolver->lookups(
            'example.com',
            IN::NAME,
            ...['A' => 'A', 'B' => 'NS']
        );
        $this->assertArrayHasKey(
            'A',
            $lookups,
            sprintf(
                '%1$s->lookups() result should contain given array key of "A"',
                $resolver::class
            )
        );
        $this->assertArrayHasKey(
            'B',
            $lookups,
            sprintf(
                '%1$s->lookups() result should contain given array key of "B"',
                $resolver::class
            )
        );
        if (!$lookups['A'] instanceof Throwable) {
            $this->assertInstanceOf(
                PacketResponseInterface::class,
                $lookups['A'],
                sprintf(
                    '%1$s->lookups()[\'A\'] result should instanceof %2$s',
                    $resolver::class,
                    PacketResponseInterface::class
                )
            );
            $this->assertInstanceOf(
                Answers::class,
                $lookups['A']->getAnswers(),
                sprintf(
                    '%1$s->lookups()[\'A\']->getAnswers() result should instanceof %2$s',
                    $resolver::class,
                    Answers::class
                )
            );
            $this->assertSame(
                'example.com',
                $lookups['A']->getAnswers()->getQuestion()->getName(),
                'Question name lookup should example.com'
            );
            $this->assertSame(
                IN::NAME,
                $lookups['A']->getAnswers()->getQuestion()->getClass()->getName(),
                sprintf(
                    'Question class name lookup should %s',
                    IN::NAME
                )
            );
            $records = $lookups['A']
                ->getAnswers()
                ->getRecords();
            // not exists
            $this->assertNull(
                $records->getFilteredType('AN'),
                'Checking record that not exists should null'
            );
            // no message
            $this->assertInstanceOf(
                A::class,
                $records->getFilteredType('A', true),
                'Checking record that exists should instance A'
            );
        } else {
            $this->assertInstanceOf(
                Exception::class,
                $lookups['A'],
                sprintf(
                    '%1$s->lookups()[\'A\'] exception result should instanceof %2$s',
                    $resolver::class,
                    Exception::class
                )
            );
        }

        if (!$lookups['B'] instanceof Throwable) {
            $this->assertInstanceOf(
                PacketResponseInterface::class,
                $lookups['B'],
                sprintf(
                    '%1$s->lookups()[\'B\'] result should instanceof %2$s',
                    $resolver::class,
                    PacketResponseInterface::class
                )
            );
            $this->assertInstanceOf(
                Answers::class,
                $lookups['B']->getAnswers(),
                sprintf(
                    '%1$s->lookups()[\'B\']->getAnswers() result should instanceof %2$s',
                    $resolver::class,
                    Answers::class
                )
            );
            $this->assertSame(
                'example.com',
                $lookups['B']->getAnswers()->getQuestion()->getName(),
                'Question name lookup should example.com'
            );
            $this->assertSame(
                IN::NAME,
                $lookups['B']->getAnswers()->getQuestion()->getClass()->getName(),
                sprintf(
                    'Question class name lookup should %s',
                    IN::NAME
                )
            );
        } else {
            $this->assertInstanceOf(
                Exception::class,
                $lookups['B'],
                sprintf(
                    '%1$s->lookups()[\'B\'] exception result should instanceof %2$s',
                    $resolver::class,
                    Exception::class
                )
            );
        }
    }
}
