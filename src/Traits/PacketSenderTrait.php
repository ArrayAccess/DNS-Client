<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Traits;

use ArrayAccess\DnsRecord\Exceptions\RequestException;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketMessageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResponseInterface;
use ArrayAccess\DnsRecord\Packet\Answers;
use ArrayAccess\DnsRecord\Packet\Header;
use ArrayAccess\DnsRecord\Packet\Message;
use ArrayAccess\DnsRecord\Packet\Response;
use ArrayAccess\DnsRecord\Utils\Caller;
use ArrayAccess\DnsRecord\Utils\Lookup;
use Throwable;
use function fclose;
use function fread;
use function fwrite;
use function is_resource;
use function max;
use function microtime;
use function min;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function stream_get_meta_data;
use function stream_set_timeout;
use function time;
use function usleep;

trait PacketSenderTrait
{
    /**
     * @var array<string, resource>
     */
    private array $sockets = [];

    abstract public function getCache(): ?CacheStorageInterface;

    /**
     * @param string $type
     * @return bool
     */
    private function cacheAbleResource(string $type): bool
    {
        return match ($type) {
            'AXFR',
            'IXFR',
            'OPT' => false,
            default => true
        };
    }

    /**
     * @param resource $resource
     * @param int|float $timeout
     * @return void
     */
    protected function setResourceTimeout($resource, int|float $timeout): void
    {
        $timeout = max(1, $timeout);
        $timeout = min($timeout, PacketRequestInterface::MAX_TIMEOUT);
        $second = (int) $timeout;
        // micro second is multiply by 1 million
        $micro = (int) (($timeout - $second) * 1000 * 1000);
        stream_set_timeout($resource, $second, $micro);
    }

    /**
     * @throws RequestException
     * @return resource
     */
    final protected function createSocketServer(
        bool $useUDP,
        string $server,
        int $port = 53
    ) {
        $protocol = $useUDP ? "udp" : "tcp";
        // udp less time
        $timeout = $useUDP ? 1.5 : 3.0;
        if (isset($this->sockets["$protocol://$server:$port"])) {
            if (is_resource($this->sockets["$protocol://$server:$port"])) {
                return $this->sockets["$protocol://$server:$port"];
            }
            unset($this->sockets["$protocol://$server:$port"]);
        }
        $hostname = "$protocol://$server";
        $handle = Caller::track(
            'fsockopen',
            $eCode,
            $eMessage,
            $hostname,
            $port,
            $errorCode,
            $errorMessage,
            $timeout
        );
        if (is_resource($handle)) {
            return $this->sockets["$protocol://$server:$port"] = $handle;
        }
        $errorCode = $errorCode?:($eCode??$errorCode);
        $errorMessage = $errorMessage?:($eMessage??$errorMessage)?:'Unknown Error';
        throw new RequestException(
            $errorMessage,
            $errorCode??0
        );
    }

    /**
     * @param PacketRequestDataInterface|DnsServerStorageInterface $packetRequest
     * @param bool $useUDP
     * @param string|null $server
     * @param int $port
     * @param mixed $serverList
     * @return array{
     *       protocol: string,
     *       server: string,
     *       socket: resource,
     *       port:int
     *  }
     * @throws RequestException
     */
    final protected function createSocket(
        PacketRequestDataInterface|DnsServerStorageInterface $packetRequest,
        bool $useUDP,
        ?string $server = null,
        int $port = 53,
        mixed &$serverList = null
    ) : array {
        if ($server) {
            $protocol = $useUDP ? "udp" : "tcp";
            try {
                $handle = $this->createSocketServer($useUDP, $server, $port);
                return [
                    'protocol' => $protocol,
                    'server' => $server,
                    'socket' => $handle,
                    'port' => $port
                ];
            } catch (RequestException $e) {
                $serverList = [
                    $server => [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                        'count' => 1,
                        'server' => $server,
                        'port' => $port,
                    ]
                ];
                throw new RequestException(
                    sprintf(
                        'Can not connect to server "%s" last error : %s',
                        $server,
                        ($e->getMessage()?:null)?:'Unknown Error'
                    ),
                    $e->getCode()
                );
            }
        }

        $definition = Lookup::socket(
            $packetRequest instanceof DnsServerStorageInterface
                ? $packetRequest
                : $packetRequest->getDnsServerStorage(),
            $useUDP,
            $serverList
        );
        $server = $definition['server'];
        $port = $definition['port'];
        $protocol = $definition['protocol'];
        $this->sockets["$protocol://$server:$port"] = $definition['socket'];
        return $definition;
    }

    /**
     * Send & Read the command
     *
     * @param resource $resource
     * @param string $query
     * @param int|float $timeout
     * @return PacketMessageInterface result from DNS serve
     * @throws RequestException
     */
    protected function sendCommand(
        &$resource,
        string $query,
        int|float $timeout
    ): PacketMessageInterface {
        $eCode = 0;
        $eMessage = '';
        $this->setResourceTimeout($resource, $timeout);
        set_error_handler(static function ($errCode, $errMsg) use (&$eCode, &$eMessage) {
            $eCode = $errCode;
            $eMessage = $errMsg;
            return true;
        });
        try {
            $status = fwrite($resource, $query);
            $info = stream_get_meta_data($resource);
            // $isUdp = $info['stream_type'] === 'udp_socket';
            if ($status === false) {
                $isTimeout = !empty($info['timed_out']);
                $eMessage = $eMessage ?: (
                $isTimeout ? 'Write process timed out' : 'Unknown error while writing data'
                );
                throw new RequestException(
                    $eMessage,
                    $eCode ?: 0
                );
            }

            // read data - on some dns response more than 512 octets
            // so, we use 4000 bytes read / TCP Size
            $readLength = Lookup::MAX_TCP_SIZE;
            // first set to 1 second
            $this->setResourceTimeout($resource, 1);
            $data = fread($resource, $readLength);
            $info = stream_get_meta_data($resource);
            // on some cases the dns server read process timed out
            if ($data === false && !empty($info['timed_out'])) {
                // delay 100 ms
                usleep(0x186a0); // 100ms
                // set request with given timeout
                $this->setResourceTimeout($resource, $timeout);
                $data = fread($resource, $readLength);
                $info = stream_get_meta_data($resource);
            }

            if ($data === false) {
                $isTimeout = !empty($info['timed_out']);
                $eMessage = $eMessage ?: (
                $isTimeout ? 'Read process timed out' : 'Unknown error while reading data'
                );
                throw new RequestException(
                    $eMessage,
                    $eCode ?: 0
                );
            }
        } finally {
            $resource = null;
            restore_error_handler();
        }

        return new Message($data);
    }

    /**
     * Create microtime
     *
     * @return float
     */
    private function createMicrotime() : float
    {
        return microtime(true) * 1000;
    }

    /**
     * @throws RequestException
     */
    protected function sendUDP(
        PacketRequestDataInterface $packetRequestData,
        int|float $timeout,
        ?string $server = null,
        int $port = 53
    ) : PacketResponseInterface {
        /** @noinspection DuplicatedCode */
        $startTime = $this->createMicrotime();
        [
            'protocol' => $protocol,
            'server' => $server,
            'socket' => $socket,
            'port' => $port
        ] = $this->createSocket($packetRequestData, true, $server, $port);
        $message = $this->sendCommand($socket, $packetRequestData->getQueryMessage(), $timeout);
        $endTime = $this->createMicrotime();
        $packet = clone $packetRequestData;
        $header = Header::fromMessage($message);
        // if header truncated -> send via tcp
        if ($header->getTC()) {
            try {
                $response = $this->sendTCP($packetRequestData, $timeout, $server, $port);
                $response->getAnswers()->getHeader();
                return $response;
            } catch (Throwable) {
            }
        }

        return new Response(
            $startTime,
            $endTime,
            $protocol,
            $server,
            $port,
            $packet,
            new Answers($message)
        );
    }

    /**
     * @throws RequestException
     */
    protected function sendTCP(
        PacketRequestDataInterface $packetRequestData,
        int|float $timeout,
        ?string $server = null,
        int $port = 53
    ) : PacketResponseInterface {
        /** @noinspection DuplicatedCode */
        $startTime = $this->createMicrotime();
        [
            'protocol' => $protocol,
            'server' => $server,
            'socket' => $socket,
            'port' => $port
        ] = $this->createSocket($packetRequestData, false, $server, $port);
        $message = $this->sendCommand($socket, $packetRequestData->getQueryMessage(), $timeout);
        $endTime = $this->createMicrotime();
        $packet = clone $packetRequestData;
        return new Response(
            $startTime,
            $endTime,
            $protocol,
            $server,
            $port,
            $packet,
            new Answers($message)
        );
    }

    private function saveResponseCache(PacketResponseInterface $response): void
    {
        $cache = $this->getCache();
        if (!$cache) {  // @phpstan-ignore-line
            return;
        }
        try {
            $cacheTime = null;
            foreach ($response->getAnswers()->getRecords() as $type) {
                if (!$this->cacheAbleResource($type->getClass()->getName())) {
                    return;
                }
                $ttl = $type->getTTL();
                if (!$cacheTime || $ttl < $cacheTime && $ttl > 0) {
                    $cacheTime = $ttl;
                }
            }
            $cacheTime ??= CacheStorageInterface::MAXIMUM_TTL;
            $cache->saveItem($response, $cacheTime);
        } catch (Throwable) {
        }
    }

    /**
     * @param PacketRequestDataInterface $packetRequestData
     * @return ?PacketResponseInterface
     */
    private function getCachePacket(PacketRequestDataInterface $packetRequestData): ?PacketResponseInterface
    {
        $cache = $this->getCache();
        if (!$cache) { // @phpstan-ignore-line
            return null;
        }
        try {
            if (($response = $cache->getItem($packetRequestData)) instanceof PacketResponseInterface) {
                $currentTime = time();
                $endTime = ($response->getEndTime() / 1000) + CacheStorageInterface::MAXIMUM_TTL;
                $shouldExpired = $endTime < $currentTime;
                if (!$shouldExpired) {
                    return $response;
                }
                $cache->deleteItem(
                    $response->getPacketData()
                );
            }
        } catch (Throwable) {
        }
        return null;
    }

    /*
     * @param string $protocol
     * @param string $server
     * @param string $port
     * @return void
     * unused
    private function closeSocket(
        string $protocol,
        string $server,
        string $port
    ): void {
        if (!isset($this->sockets["$protocol://$server:$port"])) {
            return;
        }
        if (is_resource($this->sockets["$protocol://$server:$port"])) {
            fclose($this->sockets["$protocol://$server:$port"]);
        }
        unset($this->sockets["$protocol://$server:$port"]);
    }*/

    /**
     * @param resource $resource
     * @return bool
     */
    private function closeSocketResource(&$resource): bool
    {
        if (!is_resource($resource)) {
            return false;
        }
        try {
            $meta = stream_get_meta_data($resource);
            $uri = !empty($meta['uri']) ? $meta['uri'] : null;
            fclose($resource);
            if (!$uri) {
                return true;
            }
            if (isset($this->sockets[$uri])) {
                unset($this->sockets[$uri]);
            }
            return true;
        } finally {
            $resource = null;
        }
    }

    protected function closeAllSockets(): void
    {
        foreach ($this->sockets as $key => $socket) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            unset($this->sockets[$key]);
        }
    }

    public function __destruct()
    {
        $this->closeAllSockets();
    }
}
