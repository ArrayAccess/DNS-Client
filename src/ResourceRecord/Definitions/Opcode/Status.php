<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordOpcode;
use ArrayAccess\DnsRecord\Utils\Lookup;

class Status extends AbstractResourceRecordOpcode
{
    protected string $name = 'Status';

    protected int $value = Lookup::OPCODE_STATUS;
}
