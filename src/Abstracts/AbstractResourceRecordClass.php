<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Abstracts;

use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordClassInterface;
use ArrayAccess\DnsRecord\Traits\NamedValueTrait;

/**
 * Resource Record Class
 *
 * @property-read string $name Resource record name
 * @property-read int $value Resource record value
 *
 * @see \ArrayAccess\DnsRecord\Utils\Lookup::RR_CLASS_*
 */
class AbstractResourceRecordClass implements ResourceRecordClassInterface
{
    use NamedValueTrait;
}
