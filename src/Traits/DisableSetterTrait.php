<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Traits;

trait DisableSetterTrait
{
    final public function __set(string $name, mixed $value): void
    {
        // pass
    }
}
