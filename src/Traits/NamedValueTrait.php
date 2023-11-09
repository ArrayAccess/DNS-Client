<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Traits;

/**
 * @property-read string $name
 * @property-read int $value
 */
trait NamedValueTrait
{
    use DisableSetterTrait;

    /**
     * Resource record name
     * @see Lookup::RR_CLASS_*
     *
     * @var string
     */
    protected string $name;

    /**
     * Resource record value
     * @see Lookup::RR_CLASS_*
     *
     * @var int
     */
    protected int $value;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @param string $name
     * @return int|string|null
     */
    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->getName(),
            'value' => $this->getValue(),
            default => null
        };
    }
}
