<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;

/**
 * For more controlD dns service use
 *
 * @link https://controld.com/free-dns
 */
class ControlD extends AbstractDnsServer
{
    protected string $identity = 'ControlD';

    protected string $primaryServer = '76.76.2.0';

    protected ?string $secondaryServer = '76.76.10.0';
}
