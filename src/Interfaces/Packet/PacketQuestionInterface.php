<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordClassInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQTypeDefinitionInterface;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordTypeInterface;
use Serializable;

interface PacketQuestionInterface extends Serializable
{
    /**
     * Question constructor
     *
     * @param string $name QNAME / Name
     * @param string|Int|ResourceRecordTypeInterface $type QTYPE / Type
     * @param string|int|ResourceRecordClassInterface $class QCLASS / Class
     */
    public function __construct(
        string $name,
        string|Int|ResourceRecordTypeInterface $type,
        string|int|ResourceRecordClassInterface $class
    );

    /**
     * QNAME - a domain name represented as a sequence of labels, where
     *   each label consists of a length octet followed by that
     *   number of octets.  The domain name terminates with the
     *   zero-length octet for the null label of the root.  Note
     *   that this field may be an odd number of octets; no
     *   padding is used.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * QTYPE/TYPE - a 2-octets code which specifies the type of the query.
     *   The values for this field include all codes valid for a
     *   TYPE field, together with some more general codes which
     *   can match more than one type of RR.
     *
     * @return ?ResourceRecordQTypeDefinitionInterface
     */
    public function getType(): ?ResourceRecordQTypeDefinitionInterface;

    /**
     * QCLASS - a 2-octets code that specifies the class of the query.
     *   For example, the QCLASS field is IN for the Internet.
     *
     * @return ?ResourceRecordClassInterface
     * @see \ArrayAccess\DnsRecord\Utils\Lookup::QCLASS_LIST
     */
    public function getClass(): ?ResourceRecordClassInterface;

    /**
     * The raw DNS data build from name, class & type
     *
     * @return string
     */
    public function getMessage(): string;
}
