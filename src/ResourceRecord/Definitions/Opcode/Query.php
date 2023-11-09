<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordOpcode;
use ArrayAccess\DnsRecord\Utils\Lookup;

class Query extends AbstractResourceRecordOpcode
{
    protected string $name = 'QUERY';

    protected int $value = Lookup::OPCODE_QUERY;
}
