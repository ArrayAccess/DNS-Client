<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord;

use ArrayAccess\DnsRecord\Cache\CacheStorage;
use ArrayAccess\DnsRecord\DnsServer\CustomDnsServer;
use ArrayAccess\DnsRecord\Exceptions\EmptyArgumentException;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResponseInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordOpcodeInterface;
use ArrayAccess\DnsRecord\Packet\Answers;
use ArrayAccess\DnsRecord\Packet\Header;
use ArrayAccess\DnsRecord\Packet\Question;
use ArrayAccess\DnsRecord\Packet\RequestData;
use ArrayAccess\DnsRecord\Packet\Response;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\IN;
use ArrayAccess\DnsRecord\Traits\PacketSenderTrait;
use ArrayAccess\DnsRecord\Utils\Lookup;
use Throwable;
use function strlen;
use function trim;

class Resolver
{
    use PacketSenderTrait;

    public function __construct(
        protected ?DnsServerStorage $dnsServerStorage = null,
        protected ?CacheStorageInterface $cache = null
    ) {
    }

    public function getCache(): CacheStorageInterface
    {
        if (!isset($this->cache)) {
            $this->setCache(new CacheStorage());
        }
        return $this->cache;
    }

    public function setCache(CacheStorageInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function getDnsServerStorage(): DnsServerStorage
    {
        if (!isset($this->dnsServerStorage)) {
            $this->setDnsServerStorage(DnsServerStorage::createDefault());
        }
        return $this->dnsServerStorage;
    }

    public function setDnsServerStorage(DnsServerStorage $dnsServerStorage): void
    {
        $this->dnsServerStorage = $dnsServerStorage;
    }

    /**
     * @param int|string|ResourceRecordOpcodeInterface $opcode
     * @param string $name
     * @param string $type
     * @param string $class
     * @param string ...$server
     * @return PacketRequestDataInterface
     */
    protected function createQueryOpcode(
        int|string|ResourceRecordOpcodeInterface $opcode,
        string $name,
        string $type = 'A',
        string $class = 'IN',
        string ...$server
    ) : PacketRequestDataInterface {
        // IN as default
        $class = trim($class?:'IN')?:'IN';
        $question = new Question($name, $type, $class);
        $dns = $this->getDnsServerStorage();
        if (!empty($server)) {
            $ss = [];
            foreach ($server as $s) {
                $ss[] = new CustomDnsServer($s);
            }
            $dns = new DnsServerStorage(...$ss);
        }
        return new RequestData(
            Header::createQueryHeader($opcode),
            $dns,
            $question,
        );
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $class
     * @param string ...$server
     * @return PacketRequestDataInterface
     */
    public function query(
        string $name,
        string $type = 'A',
        string $class = IN::NAME,
        string ...$server
    ): PacketRequestDataInterface {
        return $this->createQueryOpcode(
            Lookup::OPCODE_QUERY,
            $name,
            $type,
            $class,
            ...$server
        );
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $class
     * @param string ...$server
     * @return PacketRequestDataInterface
     */
    public function iQuery(
        string $name,
        string $type = 'A',
        string $class = IN::NAME,
        string ...$server
    ): PacketRequestDataInterface {
        return $this->createQueryOpcode(
            Lookup::OPCODE_IQUERY,
            $name,
            $type,
            $class,
            ...$server
        );
    }

    /**
     * @param string $name
     * @param string $class
     * @param string ...$types
     * @return array<PacketResponseInterface|Throwable>
     */
    public function lookups(
        string $name,
        string $class = IN::NAME,
        string ...$types
    ) : array {
        if (empty($types)) {
            throw new EmptyArgumentException(
                'Types could not be empty'
            );
        }

        $requests = [];
        $header = Header::createQueryHeader();
        $dns    = $this->getDnsServerStorage();
        foreach ($types as $key => $type) {
            $requests[$key] = new RequestData($header, $dns, new Question(
                $name,
                $type,
                $class
            ));
        }

        $udpServer = null;
        $tcpServer = null;
        $udpPort = null;
        $tcpPort = null;
        $tcp = null;
        $udp = null;
        $cached = [];
        foreach ($requests as $key => $request) {
            $name = $request->getQuestion()->getClass()->getName();
            if (isset($cached[$name])) {
                $requests[$key] = $cached[$name];
                continue;
            }
            try {
                $query = $request->getQueryMessage();
                $isUdp = strlen($query) < Lookup::MAX_UDP_SIZE;
                if ($isUdp && !$udp) {
                    [
                        'server' => $udpServer,
                        'socket' => $udp,
                        'port' => $udpPort
                    ] = $this->createSocket($dns, true);
                } elseif (!$isUdp && !$tcp) {
                    [
                        'server' => $tcpServer,
                        'socket' => $tcp,
                        'port' => $tcpPort,
                    ] = $this->createSocket($dns, false);
                }

                $socket = $isUdp ? $udp : $tcp;
                $server = $isUdp ? $udpServer : $tcpServer;
                $port = $isUdp ? $udpPort : $tcpPort;
                $protocol = $isUdp ? 'udp' : 'tcp';
                $startTime = $this->createMicrotime();
                $message = $this->sendCommand(
                    $socket,
                    $query,
                    $isUdp ? 2 : 5
                );
                if ($isUdp) {
                    $header = Header::fromMessage($message);
                    // if header truncated -> send via tcp
                    if ($header->getTC()) {
                        try {
                            if (!$tcp) {
                                [
                                    'server' => $tcpServer,
                                    'socket' => $tcp,
                                    'port' => $tcpPort
                                ] = $this->createSocket($dns, false);
                            }
                            $message = $this->sendCommand($tcp, $query, 5);
                            $server = $tcpServer;
                            $protocol = 'tcp';
                            $port = $tcpPort;
                        } catch (Throwable) {
                        }
                    }
                }

                $response = new Response(
                    $startTime,
                    $this->createMicrotime(),
                    $protocol,
                    $server,
                    $port,
                    $request,
                    new Answers($message)
                );

                $this->saveResponseCache($response);
                $requests[$key] = $response;
                $cached[$name] = $response;
            } catch (Throwable $e) {
                $requests[$key] = $e;
            }
        }
        unset($cached);
        $this->closeSocketResource($tcp);
        $this->closeSocketResource($udp);

        return $requests;
    }

    /**
     * Look up the dns record
     *
     * @param string $name
     * @param string $type
     * @param string $class
     * @param int|float $timeout
     * @param bool $cache
     * @param string ...$server
     * @return PacketResponseInterface
     */
    public function lookup(
        string $name,
        string $type = 'A',
        string $class = IN::NAME,
        int|float $timeout = PacketRequestInterface::DEFAULT_TIMEOUT,
        bool $cache = true,
        string ...$server
    ): PacketResponseInterface {
        $request = $this->query($name, $type, $class, ...$server);
        if ($cache && ($response = $this->getCachePacket($request))) {
            return $response;
        }

        return $request
            ->createRequest($this->getCache())
            ->send($timeout);
    }
}
