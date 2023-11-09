<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\ResourceRecord\Definitions\Opcode;

use ArrayAccess\DnsRecord\Abstracts\AbstractResourceRecordOpcode;
use ArrayAccess\DnsRecord\Utils\Lookup;

class Update extends AbstractResourceRecordOpcode
{
    protected string $name = 'UPDATE';

    protected int $value = Lookup::OPCODE_UPDATE;
}
