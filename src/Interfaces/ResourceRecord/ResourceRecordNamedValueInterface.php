<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\ResourceRecord;

use Stringable;

interface ResourceRecordNamedValueInterface extends Stringable
{
    /**
     * Resource Name
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Resource record parameter value
     *
     * @return int resource value
     */
    public function getValue() : int;
}
