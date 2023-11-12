<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions;

use ArrayAccess\DnsRecord\Exceptions\EmptyArgumentException;
use ArrayAccess\DnsRecord\Exceptions\InvalidArgumentException;
use ArrayAccess\DnsRecord\Interfaces\ResourceRecord\ResourceRecordQTypeDefinitionInterface;
use ArrayAccess\DnsRecord\Traits\NamedValueTrait;
use ArrayAccess\DnsRecord\Utils\Lookup;
use function array_search;
use function is_string;
use function sprintf;
use function strtoupper;

final class QType implements ResourceRecordQTypeDefinitionInterface
{
    use NamedValueTrait;

    /**
     * @var array<int, QType>
     */
    private static array $cachedObject = [];

    private function __construct(string $name, int $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param int|string $key
     * @return QType
     * @throws InvalidArgumentException
     */
    public static function create(int|string $key) : QType
    {
        if (is_string($key)) {
            $key = strtoupper(trim($key));
            if (!$key) {
                throw new EmptyArgumentException(
                    'QTYPE could not be empty or whitespace only'
                );
            }

            $key === '*' || $key === 'ALL' && $key = 'ANY';

            $value = Lookup::RR_TYPES[$key]??null;
            if ($value === null) {
                throw new InvalidArgumentException(
                    sprintf(
                        'QTYPE "%s" is not valid',
                        $key
                    )
                );
            }

            return self::$cachedObject[$value] ??= new self($key, $value);
        }
        if (isset(self::$cachedObject[$key])) {
            return self::$cachedObject[$key];
        }
        $value = array_search($key, Lookup::RR_TYPES, true);
        if ($value === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'QTYPE value "%s" is not valid',
                    $key
                )
            );
        }
        return self::$cachedObject[$key] ??= new self($value, $key);
    }
}
