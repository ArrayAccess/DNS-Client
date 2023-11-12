<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordMetaTypeInterface;
use ArrayAccess\DnsRecord\Packet\Message;
use ArrayAccess\DnsRecord\Utils\Lookup;
use function is_array;
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
 * Wire Format - RFC6891
 * The type is payload size
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
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-3.4.1
 * @link https://datatracker.ietf.org/doc/html/rfc6891#section-6.1.2
 */
class OPT extends AbstractResourceRecordType implements ResourceRecordMetaTypeInterface
{
    const TYPE = 'OPT';

    protected int $extended_rcode = 0;

    protected int $version = 0;

    protected int $do = 0;

    protected int $z = 0;

    protected int $option_code = 0;

    protected int $option_length = 0;

    protected string $option_data = '';

    /**
     * @param int $extendedRcode
     * @param int $do
     * @param int $version
     * @param int $z
     * @param int $classSize
     * @return OPT
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function create(
        int $extendedRcode = 0,
        int $do = 1,
        int $version = 0,
        int $z = 0,
        int $classSize = Lookup::MAX_TCP_SIZE
    ): OPT {
        $data = pack(
            "cnnCCCCn",
            0, // empty
            Lookup::resourceType('OPT')->getValue(),
            $classSize,
            $extendedRcode, // extended_rcode
            $version, // version
            ($do << 7), // DO
            $z, // z
            0 // end
        );

        // https://datatracker.ietf.org/doc/html/rfc6891#section-6.1.2
        $message = new Message($data);

        /** @noinspection PhpUnhandledExceptionInspection */
        return new self($message, 0);
    }

    protected function parseRData(string $message, int $rdataOffset): void
    {
        $data = unpack('Cextended/Cversion/Cdo/Cz', pack('N', $this->ttl));
        if (is_array($data)) {
            [
                'extended' => $this->extended_rcode,
                'version' => $this->version,
                'do' => $do,
                'z' => $this->z,
            ] = $data;
            $this->do = ($do >> 7);
        }
        if ($this->rdLength > 0) {
            $data = unpack('noption_code/noption_length', $this->rData);
            if (is_array($data)) {
                [
                    'option_code' => $this->option_code,
                    'option_length' => $this->option_length
                ] = $data;
            }
            $this->option_data = substr($this->rData, 4);
        }
    }

    public function getQueryMessage(): string
    {
        //
        // build the TTL value based on the local values
        //
        return pack(
            'C4',
            $this->extended_rcode,
            $this->version,
            ($this->do << 7),
            0
        );
    }
}
// @todo add toArray()
