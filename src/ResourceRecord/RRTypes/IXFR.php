<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\RRTypes;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordType;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordMetaTypeInterface;

/**
 * A IXFR Query format - RFC1995 Section &
 *
 *              +---------------------------------------------------+
 * Header       | OPCODE=SQUERY                                     |
 *              +---------------------------------------------------+
 * Question     | QNAME=JAIN.AD.JP., QCLASS=IN, QTYPE=IXFR          |
 *              +---------------------------------------------------+
 * Answer       | <empty>                                           |
 *              +---------------------------------------------------+
 * Authority    | JAIN.AD.JP.         IN SOA serial=1               |
 *              +---------------------------------------------------+
 * Additional   | <empty>                                           |
 *              +---------------------------------------------------+
 *
 * IXFR Response Format
 *
 *              +---------------------------------------------------+
 * Header       | OPCODE=SQUERY, RESPONSE                           |
 *              +---------------------------------------------------+
 * Question     | QNAME=JAIN.AD.JP., QCLASS=IN, QTYPE=IXFR          |
 *              +---------------------------------------------------+
 * Answer       | JAIN.AD.JP.         IN SOA serial=3               |
 *              | JAIN.AD.JP.         IN NS  NS.JAIN.AD.JP.         |
 *              | NS.JAIN.AD.JP.      IN A   133.69.136.1           |
 *              | JAIN-BB.JAIN.AD.JP. IN A   133.69.136.3           |
 *              | JAIN-BB.JAIN.AD.JP. IN A   192.41.197.2           |
 *              | JAIN.AD.JP.         IN SOA serial=3               |
 *              +---------------------------------------------------+
 * Authority    | <empty>                                           |
 *              +---------------------------------------------------+
 * Additional   | <empty>                                           |
 *              +---------------------------------------------------+
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1995#section-7
 */
class IXFR extends AbstractResourceRecordType implements ResourceRecordMetaTypeInterface
{
    const TYPE = 'IXFR';
}
// @todo add completion()
