<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Utils\Buffer;
use function base64_encode;
use function strlen;
use function unpack;

class RRSIG extends AbstractResourceRecordType
{
    const TYPE = 'RRSIG';

    protected int $sigType;

    protected int $algorithm;

    protected int $labels;

    protected int $originalttl;

    protected int $expiration;

    protected int $inception;

    protected int $keyTag;

    protected string $signer;

    protected string $signature;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        $stuff = Buffer::read($message, $rdataOffset, 18);
        [
            'type' => $this->sigType,
            'algorithm' => $this->algorithm,
            'labels' => $this->labels,
            'originalttl' => $this->originalttl,
            'expiration' => $this->expiration,
            'inception' => $this->inception,
            'keytag' => $this->keyTag,
        ] = unpack(
            "ntype/calgorithm/clabels/Noriginalttl/Nexpiration/Ninception/nkeytag",
            $stuff
        );
        $this->signer = Buffer::readLabel($message, $rdataOffset);
        $this->signature = base64_encode(
            Buffer::read($message, $rdataOffset, $this->rdLength - (strlen($this->signer) + 2) - 18)
        );
    }

    public function getSigType(): int
    {
        return $this->sigType;
    }

    public function getAlgorithm(): int
    {
        return $this->algorithm;
    }

    public function getLabels(): int
    {
        return $this->labels;
    }

    public function getOriginalttl(): int
    {
        return $this->originalttl;
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }

    public function getInception(): int
    {
        return $this->inception;
    }

    public function getKeyTag(): int
    {
        return $this->keyTag;
    }

    public function getSigner(): string
    {
        return $this->signer;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }
    public function toArray(): array
    {
        return [
            'host' => $this->getName(),
            'ttl' => $this->getTTL(),
            'class' => $this->getClass()->getName(),
            'type' => $this->getType()->getName(),
            'labels' => $this->getLabels(),
            'sigtype' => $this->getSigType(),
            'originalttl' => $this->getOriginalttl(),
            'expiration' => $this->getExpiration(),
            'inception' => $this->getInception(),
            'keytag' => $this->getKeyTag(),
            'algorithm' => $this->getAlgorithm(),
            'signer' => $this->getSigner(),
            'signature' => $this->getSignature(),
        ];
    }
}
