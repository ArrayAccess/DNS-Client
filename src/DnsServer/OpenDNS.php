<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;
use ArrayAccess\DnsRecord\Traits\DisableSetterTrait;

/**
 * @link https://www.opendns.com/setupguide/
 */
class OpenDNS extends AbstractDnsServer
{
    use DisableSetterTrait;

    protected string $primaryServer = '208.67.222.222';

    protected ?string $secondaryServer = '208.67.220.220';
}
