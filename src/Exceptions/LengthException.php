<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Exceptions;

use ArrayAccess\DnsRecord\Interfaces\Exception\ExceptionInterface;

class LengthException extends \LengthException implements ExceptionInterface
{
}
