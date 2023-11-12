<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Utils;

use Throwable;
use function restore_error_handler;
use function set_error_handler;

class Caller
{
    /**
     * Call the callable and track the error
     */
    public static function track(
        callable $callable,
        mixed &$errorCode = 0,
        mixed &$errorMessage = '',
        mixed &...$args
    ) : mixed {
        set_error_handler(static function ($code, $message) use (&$errorCode, &$errorMessage) {
            $errorCode = $code;
            $errorMessage = $message;
            return true;
        });
        try {
            return $callable(...$args);
        } catch (Throwable $e) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            // error returning null
            return null;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Call the callable & ignore error
     */
    public static function call(
        callable $callable,
        mixed ...$args
    ) : mixed {
        set_error_handler(static fn () => true);
        try {
            return $callable(...$args);
        } finally {
            restore_error_handler();
        }
    }
}
