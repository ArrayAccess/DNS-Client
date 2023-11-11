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
use ArrayAccess\DnsRecord\ResourceRecord\RRTypes\OPT;
use ArrayAccess\DnsRecord\Traits\PacketSenderTrait;
use ArrayAccess\DnsRecord\Utils\Lookup;
use Throwable;
use function strlen;
use function trim;

class Resolver
{
    use PacketSenderTrait;

    protected bool $cdFlag = false;

    protected bool $adFlag = true;

    /**
     * request DNSSEC values, by setting the DO flag to 1; this actually makes
     * the resolver add an OPT RR to the additional section, and sets the DO flag
     * in this RR to 1
     */
    protected bool $dnsSec = false;

    /**
     * if we should set the recursion desired bit to 1 or 0.
     *
     * by default, this is set to true, the DNS server to perform a recursive
     * request. If set to false, the RD bit will be set to 0, and the server will
     * not perform recursion on the request.
     */
    public bool $recurse = true;

    public function __construct(
        protected ?DnsServerStorage $dnsServerStorage = null,
        protected ?CacheStorageInterface $cache = null
    ) {
    }

    public function isCdFlag(): bool
    {
        return $this->cdFlag;
    }

    public function setCdFlag(bool $cdFlag): void
    {
        $this->cdFlag = $cdFlag;
    }

    public function isAdFlag(): bool
    {
        return $this->adFlag;
    }

    public function setAdFlag(bool $adFlag): void
    {
        $this->adFlag = $adFlag;
    }

    public function isDnsSec(): bool
    {
        return $this->dnsSec;
    }

    public function setDnsSec(bool $dnsSec): void
    {
        $this->dnsSec = $dnsSec;
    }

    public function isRecurse(): bool
    {
        return $this->recurse;
    }

    public function setRecurse(bool $recurse): void
    {
        $this->recurse = $recurse;
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
     * @param ?bool $adFlag
     * @param ?bool $cdFlag
     * @param ?bool $dnsSec
     * @param ?bool $recurse
     * @param string ...$server
     * @return PacketRequestDataInterface
     */
    public function createQueryOpcode(
        int|string|ResourceRecordOpcodeInterface $opcode,
        string $name,
        string $type = 'A',
        string $class = IN::NAME,
        ?bool $adFlag = null,
        ?bool $cdFlag = null,
        ?bool $dnsSec = null,
        ?bool $recurse = null,
        string ...$server
    ) : PacketRequestDataInterface {
        $adFlag ??= $this->isAdFlag();
        $cdFlag ??= $this->isCdFlag();
        $dnsSec ??= $this->isDnsSec();
        $recurse ??= $this->isRecurse();

        // IN as default
        $class = trim($class?:IN::NAME)?:IN::NAME;
        $class = Lookup::resourceClass($class);
        $type = Lookup::resourceType($type);
        $typeName = $type->getName();
        $isOpt = $typeName === OPT::TYPE;
        if ($isOpt) { // if is OPT fallback to A
            $type = 'A';
        }
        $question = new Question($name, $type, $class);
        $dns = $this->getDnsServerStorage();
        if (!empty($server)) {
            $ss = [];
            foreach ($server as $s) {
                $ss[] = new CustomDnsServer($s);
            }
            $dns = new DnsServerStorage(...$ss);
        }

        $header = Header::createQueryHeader($opcode, null, $adFlag, $cdFlag, $recurse);
        $requestData = new RequestData(
            $header,
            $dns,
            $question
        );
        if ($isOpt || $dnsSec) {
            $requestData
                ->getAdditionalRecords()
                ->add(OPT::create($question->getType()->getValue()));
        }
        return $requestData;
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
            $this->isAdFlag(),
            $this->isCdFlag(),
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
            $this->isAdFlag(),
            $this->isCdFlag(),
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
        $header = Header::createQueryHeader(
            adFlag: $this->adFlag,
            cdFlag: $this->cdFlag,
            rdFlag: $this->recurse
        );
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
