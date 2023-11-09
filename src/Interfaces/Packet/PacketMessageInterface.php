<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use Serializable;
use Stringable;

interface PacketMessageInterface extends Stringable, Serializable
{
    /**
     * Get record message data
     *
     * @return string
     */
    public function getMessage() : string;
}
