<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Utils;

use ArrayAccess\DnsRecord\Exceptions\EmptyArgumentException;
use ArrayAccess\DnsRecord\Exceptions\InvalidArgumentException;
use ArrayAccess\DnsRecord\Exceptions\RequestException;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerInterface;
use ArrayAccess\DnsRecord\Interfaces\DnsServer\DnsServerStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordClassInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordOpcodeInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQTypeDefinitionInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\DSO;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\IQuery;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Notify;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Query;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Status;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode\Update;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\Any;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\CH;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\CS;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\HS;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\IN;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\None;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\PrivateUse;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\ReservedEnd;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\ReservedStart;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QClass\Unassigned;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QType;
use function is_int;
use function is_object;
use function is_resource;
use function is_string;
use function sprintf;
use function str_contains;
use function strtoupper;
use function trim;

class Lookup
{
    /**
     * https://datatracker.ietf.org/doc/html/rfc6891#section-4.3
     */
    const MAX_UDP_SIZE = 512;

    /**
     * A security-aware resolver MUST support a message size of at least
     *  1220 octets, SHOULD support a message size of 4000 octets, and MUST
     *  use the "sender's UDP payload size" field in the EDNS OPT pseudo-RR
     *  to advertise the message size that it is willing to accept
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4035#section-4.1
     */
    const MAX_TCP_SIZE = 4000;

    const HEADER_SIZE = 12;

    /**
     * Query Response 0 for Query 1 for response
     */
    const QR_QUERY = 0;

    const QR_RESPONSE = 1;

    /**
     * OPCODE
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1035#section-4.1.1
     */
    const OPCODE_QUERY          = 0;        // RFC1035
    const OPCODE_IQUERY         = 1;        // RFC1035, RFC3425
    const OPCODE_STATUS         = 2;        // RFC1035
    const OPCODE_NOTIFY         = 4;        // RFC1996
    const OPCODE_UPDATE         = 5;        // RFC2136
    const OPCODE_DSO            = 6;        // RFC8490

    /**
     * List of opcodes by name
     *
     * @link https://datatracker.ietf.org/doc/html/rfc1035#section-4.1.1
     */
    const OPCODE_LIST = [
        'QUERY'  => self::OPCODE_QUERY,
        'IQUERY' => self::OPCODE_IQUERY,
        'STATUS' => self::OPCODE_STATUS,
        'NOTIFY' => self::OPCODE_NOTIFY,
        'UPDATE' => self::OPCODE_UPDATE,
        'DSO'    => self::OPCODE_DSO,
    ];

    private static array $opcodeCache = [
        self::OPCODE_QUERY  => Query::class,
        self::OPCODE_IQUERY => IQuery::class,
        self::OPCODE_STATUS => Status::class,
        self::OPCODE_NOTIFY => Notify::class,
        self::OPCODE_UPDATE => Update::class,
        self::OPCODE_DSO    => DSO::class,
    ];

    /**
     * @param int|string|ResourceRecordOpcodeInterface $opcode
     * @return ResourceRecordOpcodeInterface
     */
    public static function resourceOpcode(
        int|string|ResourceRecordOpcodeInterface $opcode
    ) : ResourceRecordOpcodeInterface {
        if (is_object($opcode)) {
            $opcode = $opcode->getValue();
        }
        if (is_string($opcode)) {
            if ($opcode === '') {
                throw new EmptyArgumentException(
                    'OPCODE could not be empty string or whitespace only'
                );
            }
            $opcode = strtoupper(trim($opcode));
            $code = self::OPCODE_LIST[$opcode]??null;
            if (!$opcode) {
                throw new InvalidArgumentException(
                    sprintf('OPCODE "%s" is not valid', $opcode)
                );
            }
            $opcode = $code;
        }
        if (!isset(self::$opcodeCache[$opcode])) {
            throw new InvalidArgumentException(
                sprintf('OPCODE "%s" is not valid', $opcode)
            );
        }
        if (is_string(self::$opcodeCache[$opcode])) {
            self::$opcodeCache[$opcode] = new self::$opcodeCache[$opcode];
        }
        return self::$opcodeCache[$opcode];
    }

    /**
     * Resource Record class
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5395#section-3.2
     * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.2.4
     */
    const QCLASS_IN = 1; // RFC1035
    const QCLASS_CH = 3; // RFC1035
    const QCLASS_HS = 4; // RFC1035
    const QCLASS_NONE = 254; // RFC2136
    const QCLASS_ANY = 255; // RFC1035
    const QCLASS_CS = 2; // RFC1035
    const QCLASS_RESERVED = 0; // RFC1035
    const QCLASS_RESERVED_END = 0xFFFF; // RFC1035

    /**
     * List of queryable Resource Record class
     * Some day just like Qclass
     * https://datatracker.ietf.org/doc/html/rfc1035#section-3.2.4
     */
    const QCLASS_LIST = [
        'IN'    => self::QCLASS_IN,        // RFC1035
        // obsolete
        'CS'    => self::QCLASS_CS,        // RFC1035
        'CH'    => self::QCLASS_CH,        // RFC1035
        'HS'    => self::QCLASS_HS,        // RFC1035
        'ANY'   => self::QCLASS_ANY,
    ];

    /**
     * DNS Response Codes
     *
     * @link https://datatracker.ietf.org/doc/html/rfc5395#section-2.3
     */
    const RCODE_NOERROR         = 0;        // RFC1035
    const RCODE_FORMERR         = 1;        // RFC1035
    const RCODE_SERVFAIL        = 2;        // RFC1035
    const RCODE_NXDOMAIN        = 3;        // RFC1035
    const RCODE_NOTIMP          = 4;        // RFC1035
    const RCODE_REFUSED         = 5;        // RFC1035
    const RCODE_YXDOMAIN        = 6;        // RFC2136
    const RCODE_YXRRSET         = 7;        // RFC2136
    const RCODE_NXRRSET         = 8;        // RFC2136
    const RCODE_NOTAUTH         = 9;        // RFC2136
    const RCODE_NOTZONE         = 10;       // RFC2136
    const RCODE_DSOTYPENI       = 11;       // RFC8490

    // 12-15 reserved / Available for assignment

    const RCODE_BADSIG          = 16;       // RFC2845
    const RCODE_BADVERS         = 16;       // RFC6891
    const RCODE_BADKEY          = 17;       // RFC2845
    const RCODE_BADTIME         = 18;       // RFC2845
    const RCODE_BADMODE         = 19;       // RFC2930
    const RCODE_BADNAME         = 20;       // RFC2930
    const RCODE_BADALG          = 21;       // RFC2930
    const RCODE_BADTRUNC        = 22;       // RFC4635
    // https://datatracker.ietf.org/doc/html/rfc7873#section-8
    const RCODE_BADCOOKIE       = 23;       // RFC7873

    const RCODE_LIST = [
        'NOERROR' => self::RCODE_NOERROR,
        'FORMERR' => self::RCODE_FORMERR,
        'SERVFAIL' => self::RCODE_SERVFAIL,
        'NXDOMAIN' => self::RCODE_NXDOMAIN,
        'NOTIMP' => self::RCODE_NOTIMP,
        'REFUSED' => self::RCODE_REFUSED,
        'YXDOMAIN' => self::RCODE_YXDOMAIN,
        'YXRRSET' => self::RCODE_YXRRSET,
        'NXRRSET' => self::RCODE_NXRRSET,
        'NOTAUTH' => self::RCODE_NOTAUTH,
        'NOTZONE' => self::RCODE_NOTZONE,
        'DSOTYPENI' => self::RCODE_DSOTYPENI,
        'BADSIG' => self::RCODE_BADSIG,
        'BADVERS' => self::RCODE_BADVERS,
        'BADKEY' => self::RCODE_BADKEY,
        'BADTIME' => self::RCODE_BADTIME,
        'BADMODE' => self::RCODE_BADMODE,
        'BADNAME' => self::RCODE_BADNAME,
        'BADALG' => self::RCODE_BADALG,
        'BADTRUNC' => self::RCODE_BADTRUNC,
        'BADCOOKIE' => self::RCODE_BADCOOKIE,
    ];

    /**
     * Resource Record Type
     *
     * @link https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#dns-parameters-2
     * @rfc6895 @link https://www.rfc-editor.org/rfc/rfc6895.html
     * @rfc9108 @link https://www.rfc-editor.org/rfc/rfc9108.html
     */
    /**
     * OPT RR
     *
     * https://datatracker.ietf.org/doc/html/rfc6891#section-6.1.1
     *
     *      +------------+--------------+------------------------------+
     *      | Field Name | Field Type   | Description                  |
     *      +------------+--------------+------------------------------+
     *      | NAME       | domain name  | MUST be 0 (root domain)      |
     *      | TYPE       | u_int16_t    | OPT (41)                     |
     *      | CLASS      | u_int16_t    | requestor's UDP payload size |
     *      | TTL        | u_int32_t    | extended RCODE and flags     |
     *      | RDLEN      | u_int16_t    | length of all RDATA          |
     *      | RDATA      | octet stream | {attribute,value} pairs      |
     *      +------------+--------------+------------------------------+
     *
     */
    const RR_TYPES = [
        // META TYPES
        'OPT'  => 41, // RFC6891 RFC3658
        'TKEY'  => 249, // RFC2930
        'TSIG'  => 250, // RFC2845

        // QTYPE
        'IXFR'  => 251, // RFC1995
        'AXFR'  => 252, // RFC1035 RFC5936
        'MAILB'  => 253, // RFC1035
        'MAILA'  => 254, // RFC1035
        'ANY'  => 255, // RFC1035 RFC6895

        // DATA TYPES
        'A' => 1, // RFC1035 - a host address
        // RFC1035 - an authoritative name server
        'NS' => 2,
        // RFC1035 - the canonical name for an alias
        'CNAME' => 5,
        // RFC1035 - marks the start of a zone of authority
        'SOA' => 6, // RFC1035 RFC2308
        // RFC1035 - a mailbox domain name
        'MB' => 7,
        // RFC1035 - a mail group member
        'MG' => 8,
        // a mail rename domain name
        'MR' => 9,
        // RFC1035 - mail exchange
        'MX' => 15, // RFC1035 RFC7505
        'PTR'  => 12, // RFC1035
        'HINFO'  => 13,
        'MINFO'  => 14,
        'TXT'  => 16, // RFC1035
        'RP'  => 17, // RFC1183
        'AFSDB'  => 18, // RFC1183 RFC5864
        'X25'  => 19, // RFC1183
        'ISDN'  => 20, // RFC1183
        'RT'  => 21, // RFC1183
        'NSAP'  => 22, // RFC1706
        'NSAP-PTR'  => 23, // RFC1348 RFC1637 RFC1706
        'SIG'  => 24, // RFC4034 RFC3755 RFC2535 RFC2536 RFC2537 RFC3008 RFC3110
        'KEY'  => 25, // RFC2930 RFC4034 RFC2535 RFC2536 RFC2537 RFC3008 RFC3110
        'PX'  => 26, // RFC2136
        'GPOS'  => 27, // RFC1712
        'AAAA'  => 28, // RFC3596
        'LOC'  => 29, // RFC1876
        'EID'  => 31,
        'NIMLOC'  => 32,
        'SRV'  => 33, // RFC2782
        'ATMA'  => 34,
        'NAPTR'  => 35, // RFC3403
        'KX'  => 36, // RFC2230
        'CERT'  => 37, // RFC4398
        'DNAME'  => 39, // RFC2672
        'SINK'  => 40,
        'APL'  => 42,
        'DS'  => 43, // RFC4034 RFC3658
        'SSHFP'  => 44, // RFC4255
        'IPSECKEY'  => 45, // RFC4025
        'RRSIG'  => 46, // RFC4034 RFC3755
        'NSEC'  => 47, // RFC4034 RFC3755
        'DNSKEY'  => 48, // RFC4034 RFC3755
        'DHCID'  => 49, // RFC4701
        'NSEC3'  => 50, // RFC5155
        'NSEC3PARAM'  => 51, // RFC5155
        'TLSA'  => 52, // RFC6698
        'HIP'  => 55, // RFC5205
        'NINFO'  => 56,
        'RKEY'  => 57,
        'TALINK'  => 58,
        'CDS'  => 59, // RFC7344
        'CDNSKEY'  => 60, // RFC7344
        'OPENPGPKEY'  => 61, // internet draft
        'CSYNC'  => 62, // RFC7477
        'SPF'  => 99, // RFC4408 RFC7208
        'UNIFO'  => 100, // IANA Reserved
        'UID'  => 101, // IANA Reserved
        'GID'  => 102, // IANA Reserved
        'UNSPEC'  => 103, // IANA Reserved
        'NID'  => 104, // RFC6742
        'L32'  => 105, // RFC6742
        'L64'  => 106, // RFC6742
        'LP'  => 107, // RFC6742
        'EUI48'  => 108, // RFC7043
        'EUI64'  => 109, // RFC7043
        'URI'  => 256, // RFC7553
        'CAA'  => 257, // RFC6844
        'TA'  => 32768,
        'DLV'  => 32769,
        'TYPE65534'  => 65534, // Eur-id
    ];

    /**
     * @param string|int|ResourceRecordQTypeDefinitionInterface|ResourceRecordTypeInterface $type
     * @return ResourceRecordQTypeDefinitionInterface
     */
    public static function resourceType(
        string|int|ResourceRecordQTypeDefinitionInterface|ResourceRecordTypeInterface $type
    ): ResourceRecordQTypeDefinitionInterface {
        return QType::create(is_object($type) ? $type->getName() : $type);
    }

    /**
     * List of ResourceRecordClassInterface classes
     *
     * @var array<class-string<ResourceRecordClassInterface>|ResourceRecordClassInterface>
     */
    private static array $classesCache = [
        'ANY' => Any::class,
        'CH' => CH::class,
        'CS' => CS::class,
        'HS' => HS::class,
        'IN' => IN::class,
        'NONE' => None::class,
        'RESERVED' => ReservedStart::class,
        'RESERVED_ENDING_ASSIGMENT' => ReservedEnd::class,
    ];

    /**
     * Get resource class Object
     *
     * @param string|int|ResourceRecordClassInterface $class
     * @return ResourceRecordClassInterface
     */
    public static function resourceClass(ResourceRecordClassInterface|string|int $class) : ResourceRecordClassInterface
    {
        $class = is_object($class) ? $class->getValue() : $class;
        if (is_int($class)) {
            $class = match ($class) {
                self::QCLASS_ANY => 'ANY',
                self::QCLASS_CH => 'CH',
                self::QCLASS_HS => 'HS',
                self::QCLASS_IN => 'IN',
                self::QCLASS_CS => 'CS',
                self::QCLASS_NONE => 'NONE',
                self::QCLASS_RESERVED => 'RESERVED',
                self::QCLASS_RESERVED_END => 'RESERVED_ENDING_ASSIGMENT',
                default => $class
            };

            if (is_string($class)) {
                // fallback check string
                return self::resourceClass($class);
            }

            if (PrivateUse::RANGE_START <= $class && PrivateUse::RANGE_END >= $class) {
                return new PrivateUse($class);
            }

            if (Unassigned::inRange($class)) {
                return new Unassigned($class);
            }

            throw new InvalidArgumentException(
                sprintf(
                    'QCLASS value "%d" is not valid',
                    $class
                )
            );
        }

        if (!($class = trim(strtoupper($class)))) {
            throw new EmptyArgumentException(
                'QCLASS could not be empty or whitespace only'
            );
        }

        if ($class === '*' || $class === 'ALL') {
            $class = 'ANY';
        }
        if ($class === 'CSNET') {
            $class = 'CS';
        }

        if (!isset(self::$classesCache[$class])) {
            throw new InvalidArgumentException(
                sprintf(
                    'QCLASS "%s" is not valid',
                    $class
                )
            );
        }
        if (is_string(self::$classesCache[$class])) {
            self::$classesCache[$class] = new self::$classesCache[$class];
        }
        return self::$classesCache[$class];
    }

    /**
     * Looping socket & connect into dns server
     *
     * @param DnsServerStorageInterface|DnsServerInterface $dnsServerStorage
     * @param bool $useUDP
     * @param null $serverList
     * @return array{protocol:string, server:string, port:int, socket: resource}
     * @throws RequestException
     */
    public static function socket(
        DnsServerStorageInterface|DnsServerInterface $dnsServerStorage,
        bool $useUDP = true,
        &$serverList = null
    ) : array {
        $serverList = [];
        $ports = [];
        if ($dnsServerStorage instanceof DnsServerInterface) {
            $dnsServerStorage = [$dnsServerStorage];
        }
        foreach ($dnsServerStorage as $server) {
            $primary   = Addresses::guessDNSServer($server->getPrimaryServer());
            $secondary = Addresses::guessDNSServer($server->getSecondaryServer());
            if ($primary) {
                $serverList[$primary] = null;
                $ports[$primary] = $server->getPort();
            }
            if ($secondary) {
                $serverList[$secondary] = null;
                $ports[$secondary] = $server->getPort();
            }
        }

        $protocol = $useUDP ? "udp" : "tcp";
        // udp less time
        $timeout = $useUDP ? 1.5 : 3.0;
        $testCount = 0;
        foreach ($serverList as $server => $status) {
            ++$testCount;
            $port = $ports[$server];
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
                return [
                    'protocol' => $protocol,
                    'server' => $server,
                    'port' => $port,
                    'socket' => $handle
                ];
            }
            $errorCode = $errorCode?:($eCode??$errorCode);
            $errorMessage = $errorMessage?:($eMessage??$errorMessage)?:'Unknown Error';
            unset($eCode, $eMessage);
            $serverList[$server] = [
                'code' => $errorCode,
                'message' => $errorMessage,
                'count' => $testCount,
                'server' => $server,
                'port' => $port,
            ];
            // if network down
            if (str_contains($errorMessage, 'Network is unreachable')) {
                break;
            }
        }

        throw new RequestException(
            sprintf(
                'Can not determine dns server after doing %d tests with last error : %s',
                $testCount,
                ($errorMessage??null)?:'Unknown Error'
            ),
            $errorCode??0
        );
    }
}
