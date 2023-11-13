<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQTypeDefinitionInterface;
use ArrayAccess\DnsRecord\ResourceRecord\Definitions\QType;
use ArrayAccess\DnsRecord\Utils\Buffer;
use function base64_encode;
use function is_array;
use function strlen;
use function unpack;

class RRSIG extends AbstractResourceRecordType
{
    const TYPE = 'RRSIG';

    protected ResourceRecordQTypeDefinitionInterface $sigType;

    protected int $algorithm;

    protected int $labels;

    protected int $originalttl;

    protected string $expiration;

    protected string $inception;

    protected int $keyTag;

    protected string $signer;

    protected string $signature;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        $stuff = Buffer::read($message, $rdataOffset, 18);
        $stuff = unpack(
            "ntype/calgorithm/clabels/Noriginalttl/Nexpiration/Ninception/nkeytag",
            $stuff
        );
        if (is_array($stuff)) {
            [
                'type' => $sigType,
                'algorithm' => $this->algorithm,
                'labels' => $this->labels,
                'originalttl' => $this->originalttl,
                'expiration' => $expiration,
                'inception' => $inception,
                'keytag' => $this->keyTag,
            ] = $stuff;
            $this->sigType = QType::create($sigType);
            $this->inception = date('YmdHis', $inception);
            $this->expiration = date('YmdHis', $expiration);
        }
        $this->signer = Buffer::readLabel($message, $rdataOffset);
        $this->signature = base64_encode(
            Buffer::read($message, $rdataOffset, $this->rdLength - (strlen($this->signer) + 2) - 18)
        );
    }

    public function getSigType(): ResourceRecordQTypeDefinitionInterface
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

    public function getExpiration(): string
    {
        return $this->expiration;
    }

    public function getInception(): string
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
            'sigtype' => $this->getSigType()->getName(),
            'original-ttl' => $this->getOriginalttl(),
            'expiration' => $this->getExpiration(),
            'inception' => $this->getInception(),
            'keytag' => $this->getKeyTag(),
            'algorithm' => $this->getAlgorithm(),
            'signer' => $this->getSigner(),
            'signature' => $this->getSignature(),
        ];
    }
}
