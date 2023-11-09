<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;

/**
 * @link https://www.opendns.com/setupguide/
 */
class OpenDNS extends AbstractDnsServer
{
    protected string $identity = 'openDNS';

    protected string $primaryServer = '208.67.222.222';

    protected ?string $secondaryServer = '208.67.220.220';
}
