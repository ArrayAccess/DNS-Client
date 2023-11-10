<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Utils;

use function error_clear_last;
use function restore_error_handler;
use function set_error_handler;

class Caller
{
    /**
     * Call the callable and track the error
     */
    public static function track(
        callable $callable,
        &$errorCode = 0,
        &$errorMessage = '',
        &...$args
    ) {
        set_error_handler(static function ($code, $message) use (&$errorCode, &$errorMessage) {
            $errorCode = $code;
            $errorMessage = $message;
            error_clear_last();
        });
        try {
            return $callable(...$args);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Call the callable & ignore error
     */
    public static function call(
        callable $callable,
        ...$args
    ) {
        set_error_handler(static fn() => error_clear_last());
        try {
            return $callable(...$args);
        } finally {
            restore_error_handler();
        }
    }
}
