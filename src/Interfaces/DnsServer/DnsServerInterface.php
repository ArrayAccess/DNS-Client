<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\DnsServer;

use Serializable;

/**
 * Dns server definition grouping
 * DNS using port 53 follow the RFC 1035 section 4.2.1.
 * Commonly DNS server contains primary & secondary server for faster redundancy and resiliency
 *
 * @link https://datatracker.ietf.org/doc/html/rfc1035#section-4.2.1
 */
interface DnsServerInterface extends Serializable
{
    /**
     * Object Identity as unique key name to store in DnsServerStorage
     *
     * @return string
     */
    public function getIdentity() : string;

    /**
     * Dns server name for friendly name
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Dns server port
     *
     * @return int
     */
    public function getPort() : int;

    /**
     * Getting primary dns
     *
     * @return string server eg: 8.8.8.8
     */
    public function getPrimaryServer() : string;

    /**
     * Secondary server
     *
     * @return ?string
     */
    public function getSecondaryServer() : ?string;
}
