<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use function base64_encode;
use function is_array;
use function substr;
use function unpack;

/**
 *
 * The CERT resource record (RR) has the structure given below.  Its RR
 * type code is 37. - RFC4398
 *
 *                           1 1 1 1 1 1 1 1 1 1 2 2 2 2 2 2 2 2 2 2 3 3
 *       0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *      |             type              |             key tag           |
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *      |   algorithm   |                                               /
 *      +---------------+            certificate or CRL                 /
 *      /                                                               /
 *      +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-|
 *
 * @link https://datatracker.ietf.org/doc/rfc4398/
 */
class CERT extends AbstractResourceRecordType
{
    const TYPE = 'CERT';

    protected int $format;

    protected int $keyTag;

    protected string $algorithm;

    protected string $certificate;

    /**
     * @inheritdoc
     */
    protected function parseRData(string $message, int $rdataOffset): void
    {
        if ($this->rdLength < 6) {
            return;
        }
        $data = unpack('nformat/nkeyTag/Calgorithm', $this->rData);
        if (!is_array($data)) {
            return;
        }
        //
        // unpack the format, keytag and algorithm
        //
        [
            'format' => $this->format,
            'keyTag' => $this->keyTag,
            'algorithm' => $this->algorithm
        ] = $data;

        //
        // copy the certificate
        //
        $this->certificate  = base64_encode(substr($this->rData, 5, $this->rdLength - 5));
    }

    public function getFormat(): int
    {
        return $this->format;
    }

    public function getKeyTag(): int
    {
        return $this->keyTag;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }
}
// @todo add toArray()
