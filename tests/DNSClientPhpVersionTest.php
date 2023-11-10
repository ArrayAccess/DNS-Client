<?php
declare(strict_types=1);

namespace {

    use PHPUnit\Framework\TestCase;

    class DNSClientPhpVersionTest extends TestCase
    {
        public function testPhpVersion()
        {
            $this->assertTrue(
                version_compare(phpversion(), '8.1', '>='),
                sprintf(
                    'Php version should greater than %s',
                    '8.1'
                )
            );
        }
    }
}
