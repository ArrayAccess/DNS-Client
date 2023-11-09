<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordMetaTypeInterface;
use ArrayAccess\DnsRecord\Packet\Message;
use ArrayAccess\DnsRecord\Utils\Buffer;
use ArrayAccess\DnsRecord\Utils\Lookup;
use function pack;
use function unpack;

/**
 * A RDATA format - RFC1035 Section 3.4.1
 * ADDRESS A 32-bit Internet address.
 *
 *                  +0 (MSB)                +1 (LSB)
 *          +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *       0: |   EXTENDED-RCODE      |       VERSION         |
 *          +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *       2: |DO|                    Z                       |
 *          +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.4.1
 */
class OPT extends AbstractResourceRecordType implements ResourceRecordMetaTypeInterface
{
    const TYPE = 'OPT';

    protected int $extended_rcode;

    protected int $version;

    protected int $do;

    protected int $z;

    protected int $option_code = 0;

    protected int $option_length = 0;

    protected string $option_data = '';

    /**
     * @param string $name
     * @param int $do
     * @return OPT
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function create(string $name = '', int $do = 1): OPT
    {
        $name = $name ? Buffer::compressLabel($name) : "\0";
        $message = new Message(
            $name . pack(
                "nnNn",
                Lookup::resourceType('OPT')->getValue(),
                Lookup::QCLASS_LIST['IN'],
                pack(
                    'CCCC',
                    0,
                    0,
                    ($do << 7),
                    0
                ),
                0
            )
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        return new self($message, 0);
    }

    protected function generateTTL()
    {
        return unpack('N', $this->getQueryMessage())[1];
    }

    protected function parseRData(string $message, int $rdataOffset): void
    {
        [
            'extended' => $this->extended_rcode,
            'version' => $this->version,
            'do' => $do,
            'z' => $this->z,
        ] = unpack('Cextended/Cversion/Cdo/Cz', pack('N', $this->ttl));
        $this->do               = ($do >> 7);
        if ($this->rdLength > 0) {
            [
                'option_code' => $this->option_code,
                'option_length' => $this->option_length
            ] = unpack('noption_code/noption_length', $this->rData);
            $this->option_data = substr($this->rData, 4);
        }
    }

    public function getQueryMessage(): string
    {
        //
        // build the TTL value based on the local values
        //
        return pack(
            'CCCC',
            $this->extended_rcode,
            $this->version,
            ($this->do << 7),
            0
        );
    }
}
