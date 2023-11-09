<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Abstracts;

use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordOpcodeInterface;
use ArrayAccess\DnsRecord\Traits\NamedValueTrait;

/**
 * Resource Record Opcode
 *
 * @property-read string $name Resource opcode name
 * @property-read int $value Resource opcode value
 *
 * @see \ArrayAccess\DnsRecord\Utils\Lookup::OPCODE_*
 */
class AbstractResourceRecordOpcode implements ResourceRecordOpcodeInterface
{
    use NamedValueTrait;
}
