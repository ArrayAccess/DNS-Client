<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use function base64_encode;
use function is_array;
use function sprintf;
use function unpack;

/**
 * DNSKEY Resource Record - RFC4034 section 2.1
 *
 *                           1 1 1 1 1 1 1 1 1 1 2 2 2 2 2 2 2 2 2 2 3 3
 *       0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *      |              Flags            |    Protocol   |   Algorithm   |
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *      /                                                               /
 *      /                            Public Key                         /
 *      /                                                               /
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 * DNSKEY RR Example:
 *
 * example.com. 86400 IN DNSKEY 256 3 5 ( AQPSKmynfzW4kyBv015MUG2DeIQ3
 *                                          Cbl+BBZH4b/0PY1kxkmvHjcZc8no
 *                                          kfzj31GajIQKY+5CptLr3buXA10h
 *                                          WqTkF7H6RfoRqXQeogmMHfpftf6z
 *                                          Mv1LyBUgia7za6ZEzOJBOztyvhjL
 *                                          742iU/TpPSEDhm2SNKLijfUppn1U
 *                                          aNvv4w==  )
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4034#section-2.1
 * @link https://datatracker.ietf.org/doc/html/rfc4034#section-2.3
 */
class DNSKEY extends AbstractResourceRecordType
{
    const TYPE = 'DNSKEY';
    protected int $protocol;

    /**
     * Algorithm
     *
     * @var int $algorithm
     * @link https://www.iana.org/assignments/dns-sec-alg-numbers/dns-sec-alg-numbers.xhtml#table-dns-sec-alg-numbers-1
     */
    protected int $algorithm;

    protected int $flags;

    protected string $publicKey;

    protected int $keyTag;

    protected bool $zoneKey;

    protected bool $zoneSep;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        $data = unpack("nflags/Cprotocol/Calgorithm/a*pubKey", $this->rData);
        if (!is_array($data)) {
            return;
        }

        [
            'flags' => $this->flags,
            // https://datatracker.ietf.org/doc/html/rfc4034#section-2.1.2
            'protocol' => $this->protocol,
            'algorithm' => $this->algorithm,
            'pubKey' => $pubKey,
        ] = $data;
        //  0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 = 16
        // convert binary flags
        $flags = sprintf("%016b\n", $this->flags);
        // encode public key
        $this->publicKey = base64_encode($pubKey);
        /**
         * @link https://datatracker.ietf.org/doc/html/rfc4034#appendix-B
         */
        $ac = 0;
        for ($i = 0; $i < $this->rdLength; ++$i) {
            $ac += ($i & 1) ? ord($this->rData[$i]) : ord($this->rData[$i]) << 8;
        }
        $ac += ($ac >> 16) & 0xFFFF;
        $this->keyTag  = $ac & 0XFFFF;

        /**
         * Bit 7 of the Flags field is the Zone Key flag.  If bit 7 has value 1,
         * then the DNSKEY record holds a DNS zone key, and the DNSKEY RR's
         * owner name MUST be the name of a zone.  If bit 7 has value 0, then
         * the DNSKEY record holds some other type of DNS public key and MUST
         * NOT be used to verify RRSIGs that cover RRsets.
         *
         * Bit 15 of the Flags field is the Secure Entry Point flag, described
         * in [RFC3757].  If bit 15 has value 1, then the DNSKEY record holds a
         * key intended for use as a secure entry point.
         *
         * @link https://datatracker.ietf.org/doc/html/rfc4034#section-2.1.1
         */
        $this->zoneKey = ((int)$flags[7]) === 1;
        $this->zoneSep = ((int)$flags[15]) === 1;
    }

    public function getProtocol(): int
    {
        return $this->protocol;
    }

    public function getAlgorithm(): int
    {
        return $this->algorithm;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getKeyTag(): int
    {
        return $this->keyTag;
    }

    public function isZoneKey(): bool
    {
        return $this->zoneKey;
    }

    public function isZoneSep(): bool
    {
        return $this->zoneSep;
    }

    public function toArray(): array
    {
        return [
            'host' => $this->getName(),
            'class' => $this->getClass()->getName(),
            'ttl' => $this->getTTL(),
            'type' => $this->getType()->getName(),
            'flags' => $this->getFlags(),
            'protocol' => $this->getProtocol(),
            'algorithm' => $this->getAlgorithm(),
            'keytag' => $this->getKeyTag(),
            'zonekey' => $this->isZoneKey(),
            'zonesep' => $this->isZoneSep(),
            'pubkey' => $this->getPublicKey(),
        ];
    }
}
