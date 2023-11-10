<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;
use ArrayAccess\DnsRecord\Traits\DisableSetterTrait;

/**
 * For more controlD dns service use
 *
 * @link https://controld.com/free-dns
 */
class ControlD extends AbstractDnsServer
{
    use DisableSetterTrait;

    protected string $primaryServer = '76.76.2.0';

    protected ?string $secondaryServer = '76.76.10.0';
}
