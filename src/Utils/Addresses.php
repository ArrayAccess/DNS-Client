<?php
/** @noinspection PhpComposerExtensionStubsInspection */
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Utils;

use Stringable;
use function array_reverse;
use function chr;
use function explode;
use function function_exists;
use function idn_to_ascii;
use function idn_to_utf8;
use function implode;
use function inet_ntop;
use function inet_pton;
use function intdiv;
use function ip2long;
use function is_string;
use function long2ip;
use function ord;
use function parse_url;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strtolower;
use function trim;

class Addresses
{
    /**
     * Guess the given dns server, first use IP and then Domain name
     *
     * @param mixed $server
     * @return string|null
     */
    public static function guessDNSServer(mixed $server): ?string
    {
        // ip
        return self::filterIp($server)??self::filterDomain($server);
    }

    /**
     * Filter IPv6
     *
     * @param string $ip
     * @return ?string returning null if invalid
     */
    public static function filterIpv6(string $ip): ?string
    {
        // ipv6 should contains colon
        if (!str_contains($ip, ':')) {
            return null;
        }
        // Converts a human-readable IP address to its packed in_addr representation
        $ip = inet_pton($ip);
        return $ip ? (inet_ntop($ip) ?: null) : null;
    }

    /**
     * Filter IPv4
     *
     * @param string $ip
     * @return ?string returning null if invalid
     */
    public static function filterIpv4(string $ip): ?string
    {
        // ipv4 should contain dot
        if (!str_contains($ip, '.')) {
            return null;
        }
        // convert ip4 to long integer
        $ip = ip2long($ip);
        return $ip ? (long2ip($ip) ?: null) : null;
    }

    /**
     * Filter IP address
     *
     * @param string|mixed $ip the IP address
     * @return ?string
     */
    public static function filterIp(mixed $ip): ?string
    {
        if (!is_string($ip)) {
            return null;
        }
        return (self::filterIpv6($ip) ?: self::filterIpv4($ip))?:null;
    }

    /**
     * Create reverse dns
     *
     * @link https://www.rfc-editor.org/rfc/rfc8501.html
     *
     * @param string $ip
     * @return ?string
     */
    public static function reverseIp(string $ip): ?string
    {
        $ip = self::filterIpv6($ip) ?: self::filterIpv4($ip);
        if (!$ip) {
            return null;
        }
        $separator = str_contains($ip, ':') ? ':' : '.';
        $value = implode('.', array_reverse(explode($separator, $ip)));
        $value .= $separator === '.' ? '.in-addr.arpa' : '.ip6.arpa';
        return $value;
    }

    /**
     * Filter domain name as possible
     *
     * @param mixed $domainName the domain name
     * @return ?string null if invalid
     *
     * @noinspection PhpComposerExtensionStubsInspection
     */
    public static function filterDomain(mixed $domainName): ?string
    {
        // convert to string if maybe the domain name is PSR UriInterface
        if ($domainName instanceof Stringable) {
            $domainName = (string) $domainName;
        }

        if (!is_string($domainName)) {
            return null;
        }
        static $intl_idn = null;
        $intl_idn ??= function_exists('idn_to_ascii')
            && function_exists('idn_to_utf8');

        $domainName = strtolower(trim($domainName));
        if (!$domainName) {
            return null;
        }
        // get domain name if the given argument is an url
        // protocol://user:pass@domainname.com:port/path
        if (preg_match(
            '~^(?:(?:[a-z]+:)?//)?(?:[^:]*(?::[^@]*)?@)?([^/:]+)(?::\d+|[#?/]|$)~',
            $domainName,
            $match
        )) {
            $domainName = $match[1];
        }

        if ($domainName === 'localhost') {
            return $domainName;
        }
        // the maximum is 255 octets RFC2181
        // and minimum 2 characters
        if (strlen($domainName) > 255 || strlen($domainName) < 2) {
            return null;
        }

        // check common part of uri
        if (preg_match('~[:/#?@]~', $domainName)) {
            $parsed = parse_url($domainName);
            // using false positive eg: aaa:example.com
            if (!isset($parsed['host'])
                && isset($parsed['scheme'], $parsed['path'])
                && !str_contains($parsed['path'], '/')
            ) {
                $domainName = $parsed['path'];
            }
            if (!$domainName) {
                return null;
            }
        }
        $isAscii = false;
        $labels = [];
        foreach (explode('.', $domainName) as $label) {
            // contain whitespace
            if (($nLabel = trim($label)) === '' || $nLabel !== $label) {
                return null;
            }
            // each separator could not contain more than 63 characters
            if (strlen($label) > 63) {
                return null;
            }
            if (!$isAscii) {
                $isAscii = str_starts_with($label, 'xn--');
            }
            $labels[] = $label;
        }

        if ($intl_idn) {
            $domainName = $isAscii ? idn_to_utf8($domainName) : idn_to_ascii($domainName);
            // revert
            $domainName = ($isAscii ? idn_to_ascii($domainName) : idn_to_utf8($domainName)) ?: null;
            if (!$domainName) {
                return null;
            }
            $labels = $domainName;
        } else {
            foreach ($labels as &$label) {
                if (preg_match('/[^x00-x7F]/', $domainName)) {
                    $label = 'xn--' . self::punycodeEncode($label);
                    if (!$label || strlen($label) > 63) {
                        return null;
                    }
                }
            }
            unset($label);
            $labels = implode('.', $labels);
        }
        if (strlen($labels) > 255
            || strlen($labels) < 2
            // domain except localhost should contain extension
            // xn-- // (?i)[a-z0-9-]
            || !preg_match(
                '~[a-z0-9\-](?:[a-z0-9-]*[a-z0-9]+(?:\.[a-z0-9-]*[a-z0-9]+)*)?\.(?:xn--[a-z0-9_]+|[a-z]+)$~',
                $labels
            )
        ) {
            return null;
        }


        return $domainName;
    }

    /**
     * @see https://tools.ietf.org/html/rfc3492#section-6.1
     *
     * @param int $delta
     * @param int $numPoints
     * @param bool $firstTime
     *
     * @return int
     */
    private static function adaptBias(int $delta, int $numPoints, bool $firstTime): int
    {
        // xxx >> 1 is a faster way of doing intdiv(xxx, 2)
        $delta = $firstTime ? intdiv($delta, 700) : $delta >> 1;
        $delta += intdiv($delta, $numPoints);
        $k = 0;

        while ($delta > ((36 - 1) * 26) >> 1) {
            $delta = intdiv($delta, 36 - 1);
            $k += 36;
        }

        return $k + intdiv((36 - 1 + 1) * $delta, $delta + 38);
    }

    /**
     * Encode the digit
     *
     * @param int $d
     *
     * @return string
     */
    private static function encodeDigit(int $d): string
    {
        return chr($d + 22 + 75 * ($d < 26 ? 1 : 0) - (0 << 5));
    }

    /**
     * @see https://tools.ietf.org/html/rfc3492#section-6.3
     *
     * @param string $input
     *
     * @return ?string
     */
    private static function punycodeEncode(string $input): ?string
    {
        $n = 128;
        $delta = 0;
        $out = 0;
        $bias = 72;
        $inputLength = 0;
        $output = '';
        $iter = self::utf8Decode($input);
        foreach ($iter as $codePoint) {
            ++$inputLength;
            if ($codePoint < 0x80) {
                $output .= chr($codePoint);
                ++$out;
            }
        }

        $h = $out;
        if (($b = $out) > 0) {
            $output .= '-';
            //$out++;
        }

        while ($h < $inputLength) {
            $m = 2147483647;

            foreach ($iter as $codePoint) {
                if ($codePoint >= $n && $codePoint < $m) {
                    $m = $codePoint;
                }
            }

            if ($m - $n > intdiv(2147483647 - $delta, $h + 1)) {
                return null;
            }

            $delta += ($m - $n) * ($h + 1);
            $n = $m;

            foreach ($iter as $codePoint) {
                if ($codePoint < $n && 0 === ++$delta) {
                    return null;
                }
                if ($codePoint === $n) {
                    $q = $delta;
                    for ($k = 36; /* no condition */; $k += 36) {
                        if ($k <= $bias) {
                            $t = 1;
                        } elseif ($k >= $bias + 26) {
                            $t = 26;
                        } else {
                            $t = $k - $bias;
                        }

                        if ($q < $t) {
                            break;
                        }

                        $qMinusT = $q - $t;
                        $baseMinusT = 36 - $t;
                        $output .= self::encodeDigit($t + $qMinusT % $baseMinusT);
                        //$out++;
                        $q = intdiv($qMinusT, $baseMinusT);
                    }
                    $output .= self::encodeDigit($q);
                    //$out++;
                    $bias = self::adaptBias($delta, $h + 1, $h === $b);
                    $delta = 0;
                    ++$h;
                }
            }

            ++$delta;
            ++$n;
        }

        return $output;
    }

    /**
     * Takes a UTF-8 encoded string and converts it into a series of integer code points. Any
     * invalid byte sequences will be replaced by a U+FFFD replacement code point.
     *
     * @see https://encoding.spec.whatwg.org/#utf-8-decoder
     *
     * @param string $input
     *
     * @return array<int, int>
     */
    private static function utf8Decode(string $input): array
    {
        $bytesSeen = 0;
        $bytesNeeded = 0;
        $lowerBoundary = 0x80;
        $upperBoundary = 0xBF;
        $codePoint = 0;
        $codePoints = [];
        $length = strlen($input);

        for ($i = 0; $i < $length; ++$i) {
            $byte = ord($input[$i]);

            if (0 === $bytesNeeded) {
                if ($byte >= 0x00 && $byte <= 0x7F) {
                    $codePoints[] = $byte;

                    continue;
                }

                if ($byte >= 0xC2 && $byte <= 0xDF) {
                    $bytesNeeded = 1;
                    $codePoint = $byte & 0x1F;
                } elseif ($byte >= 0xE0 && $byte <= 0xEF) {
                    if (0xE0 === $byte) {
                        $lowerBoundary = 0xA0;
                    } elseif (0xED === $byte) {
                        $upperBoundary = 0x9F;
                    }

                    $bytesNeeded = 2;
                    $codePoint = $byte & 0xF;
                } elseif ($byte >= 0xF0 && $byte <= 0xF4) {
                    if (0xF0 === $byte) {
                        $lowerBoundary = 0x90;
                    } elseif (0xF4 === $byte) {
                        $upperBoundary = 0x8F;
                    }

                    $bytesNeeded = 3;
                    $codePoint = $byte & 0x7;
                } else {
                    $codePoints[] = 0xFFFD;
                }

                continue;
            }

            if ($byte < $lowerBoundary || $byte > $upperBoundary) {
                $codePoint = 0;
                $bytesNeeded = 0;
                $bytesSeen = 0;
                $lowerBoundary = 0x80;
                $upperBoundary = 0xBF;
                --$i;
                $codePoints[] = 0xFFFD;
                continue;
            }

            $lowerBoundary = 0x80;
            $upperBoundary = 0xBF;
            $codePoint = ($codePoint << 6) | ($byte & 0x3F);

            if (++$bytesSeen !== $bytesNeeded) {
                continue;
            }

            $codePoints[] = $codePoint;
            $codePoint = 0;
            $bytesNeeded = 0;
            $bytesSeen = 0;
        }

        // String unexpectedly ended, so append a U+FFFD code point.
        if (0 !== $bytesNeeded) {
            $codePoints[] = 0xFFFD;
        }

        return $codePoints;
    }
}
