<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\DnsServer;

use ArrayAccess\DnsRecord\Abstracts\AbstractDnsServer;
use ArrayAccess\DnsRecord\Traits\DisableSetterTrait;
use function sprintf;

/**
 * User defined dns server
 */
class CustomDnsServer extends AbstractDnsServer
{
    use DisableSetterTrait;

    /**
     * @param string $primaryServer
     * @param ?string $secondaryServer
     * @param ?string $identity
     * @param ?string $name
     */
    public function __construct(
        string $primaryServer,
        ?string $secondaryServer = null,
        ?string $identity = null,
        ?string $name = null
    ) {
        $this->primaryServer = $primaryServer;
        $this->secondaryServer = $secondaryServer;
        if (!$identity) {
            $this->identity = sprintf(
                '%s/%s/%s',
                $this::class,
                $primaryServer,
                $secondaryServer
            );
        }
        if (!$name) {
            $this->name = sprintf(
                'Custom DNS Server : %s',
                $secondaryServer
                ? sprintf(
                    '%s & %s',
                    $primaryServer,
                    $secondaryServer
                ) : $primaryServer
            );
        }
    }

    /**
     * Create custom dns server instance
     *
     * @param string $primaryServer
     * @param ?string $secondaryServer
     * @param ?string $identity
     * @param ?string $name
     * @return static
     */
    public static function create(
        string $primaryServer,
        ?string $secondaryServer = null,
        ?string $identity = null,
        ?string $name = null
    ) : static {
        return new static($primaryServer, $secondaryServer, $identity, $name);
    }
}
