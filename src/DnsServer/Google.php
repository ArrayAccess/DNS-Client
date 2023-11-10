<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;
use ArrayAccess\DnsRecord\Traits\DisableSetterTrait;

/**
 * @link https://developers.google.com/speed/public-dns/docs/using
 */
class Google extends AbstractDnsServer
{
    use DisableSetterTrait;

    protected string $primaryServer = '8.8.8.8';

    protected ?string $secondaryServer = '8.8.4.4';
}
