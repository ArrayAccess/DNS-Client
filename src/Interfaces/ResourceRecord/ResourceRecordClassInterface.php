<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\ResourceRecord;

/**
 * Resource Record Classes
 *
 * https://datatracker.ietf.org/doc/html/rfc5395#section-3.2
 * https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml
 */
interface ResourceRecordClassInterface extends ResourceRecordNamedValueInterface
{
    /**
     * Class name
     *
     * @see \ArrayAccess\DnsRecord\Utils\Lookup::QCLASS_LIST
     * @see \ArrayAccess\DnsRecord\Utils\Lookup::RR_CLASS_*
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Resource record parameter value
     *
     * @return int resource value
     */
    public function getValue() : int;
}
