<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;
use ArrayAccess\DnsRecord\Traits\DisableSetterTrait;

/**
 * @link https://developers.cloudflare.com/1.1.1.1/ip-addresses/
 */
class Cloudflare extends AbstractDnsServer
{
    use DisableSetterTrait;

    protected string $primaryServer = '1.1.1.1';

    protected ?string $secondaryServer = '1.1.0.0';
}
