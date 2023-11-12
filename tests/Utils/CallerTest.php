<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord\Utils;

use ArrayAccess\DnsRecord\Exceptions\Exception;
use ArrayAccess\DnsRecord\Utils\Caller;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use function error_get_last;
use function microtime;
use function sprintf;
use function strtoupper;
use function trigger_error;
use const E_USER_WARNING;

class CallerTest extends TestCase
{

    #[WithoutErrorHandler]
    public function testCall() : void
    {
        $message = 'sample: '.md5(microtime());
        $code = rand(0, 10);
        Caller::track(static function () use ($message, $code) {
            throw new Exception(
                $message,
                $code
            );
        }, $erorCode, $errorMesage);
        $this->assertSame(
            $code,
            $erorCode,
            sprintf(
                'Caller::track() use error code should equal with thrown : %d',
                $code
            )
        );
        $this->assertSame(
            $message,
            $errorMesage,
            sprintf(
                'Caller::track() use error message should identical with thrown : %s',
                $message
            )
        );
        $message = 'lowercaseToUppercase';
        $this->assertSame(
            Caller::track('strtoupper', $errorCode, $errorMessage, $message),
            strtoupper($message),
            sprintf(
                'Caller::track(\'strtoupper\', $errorCode, $errorMessage, "%1$s") should identical strtoupper(%1$s)',
                $message
            )
        );
    }

    #[WithoutErrorHandler]
    public function testTrack() : void
    {
        $message = 'Message 1';
        @trigger_error($message, E_USER_WARNING);
        Caller::call(function () {
            // call trigger
            trigger_error('Message 2');
        });
        $this->assertIsArray(
            error_get_last(),
            'Caller::call() should not clear the last error & error_get_last() should array return'
        );
        $this->assertSame(
            $message,
            error_get_last()['message'],
            sprintf(
                'error_get_last()[\'message\'] should return string "%1$s"',
                $message
            )
        );
        $this->assertSame(
            E_USER_WARNING,
            error_get_last()['type'],
            sprintf(
                'error_get_last()[\'type\'] should return code: %1$s',
                E_USER_WARNING
            )
        );

        $message = 'lowercaseToUppercase';
        $this->assertSame(
            Caller::call('strtoupper', $message),
            strtoupper($message),
            sprintf(
                'Caller::call(\'strtoupper\', "%1$s") should identical strtoupper(%1$s)',
                $message
            )
        );
    }
}
