<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Exceptions;

use ArrayAccess\DnsRecord\Interfaces\Exception\CacheExceptionInterface;

class CacheException extends Exception implements CacheExceptionInterface
{
}
