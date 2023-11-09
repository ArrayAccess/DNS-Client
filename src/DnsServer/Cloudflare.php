<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;

/**
 * @link https://developers.cloudflare.com/1.1.1.1/ip-addresses/
 */
class Cloudflare extends AbstractDnsServer
{
    protected string $identity = 'Cloudflare';

    protected string $primaryServer = '1.1.1.1';

    protected ?string $secondaryServer = '1.1.0.0';
}
