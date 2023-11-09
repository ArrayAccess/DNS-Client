<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Exceptions;

use ArrayAccess\DnsRecord\Interfaces\Exception\ExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{

}
