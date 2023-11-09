<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;

/**
 * @link https://developers.cloudflare.com/1.1.1.1/ip-addresses/#1111-for-families
 * For block adult content use:
 *
 * 2606:4700:4700::1113
 * 2606:4700:4700::1003
 */
class CloudflareFamily extends AbstractDnsServer
{
    protected string $identity = 'CloudflareFamily';

    protected string $primaryServer = '1.1.1.2';

    protected ?string $secondaryServer = '1.0.0.2';
}
