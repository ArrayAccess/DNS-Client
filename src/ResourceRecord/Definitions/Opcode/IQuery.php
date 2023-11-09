<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordOpcode;
use ArrayAccess\DnsRecord\Utils\Lookup;

class IQuery extends AbstractResourceRecordOpcode
{
    protected string $name = 'IQUERY';

    protected int $value = Lookup::OPCODE_IQUERY;
}
