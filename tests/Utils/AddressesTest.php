<?php
declare(strict_types=1);

namespace Tests\ArrayAccess\DnsRecord\Utils;

use ArrayAccess\DnsRecord\Utils\Addresses;
use PHPUnit\Framework\TestCase;
use function array_reverse;
use function explode;
use function implode;
use function sprintf;

class AddressesTest extends TestCase
{

    public function testFilterDomain()
    {
        $baseDomain = 'example.com'; // base domain
        $httpsDomainWithPort = "https://$baseDomain:443/";
        $httpsDomainWithUser = "https://user@$baseDomain";
        $httpsDomainWithUserPassword = "https://user:pass@$baseDomain";
        $httpsDomainWithUserEmptyPassword = "https://user:@$baseDomain";
        $httpsDomainWithUserPasswordAndPort = "https://user:pass@$baseDomain:443";
        $httpsDomainWithUserEmptyPasswordAndPort = "https://user:@$baseDomain:443";
        $httpsDomainWithEmail = "user@$baseDomain";
        $httpsDomain = "https://$baseDomain/";
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomainWithPort),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomainWithPort
            )
        );
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomainWithUser),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomainWithUser
            )
        );
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomainWithUserPassword),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomainWithUserPassword
            )
        );
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomainWithUserEmptyPassword),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomainWithUserEmptyPassword
            )
        );
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomainWithUserPasswordAndPort),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomainWithUserPasswordAndPort
            )
        );
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomainWithUserEmptyPasswordAndPort),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomainWithUserEmptyPasswordAndPort
            )
        );
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomainWithEmail),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomainWithEmail
            )
        );
        $invalidDomain = 'invalid-domain-name';
        $this->assertSame(
            $baseDomain,
            Addresses::filterDomain($httpsDomain),
            sprintf(
                'Addresses::filterDomain("%1$s") should return "%1$s"',
                $httpsDomain
            )
        );
        // if invalid domain name
        $this->assertNull(
            Addresses::filterDomain($invalidDomain),
            sprintf(
                'Addresses::filterDomain("%1$s") should return null',
                $invalidDomain
            )
        );
    }

    public function testFilterIp()
    {
        $invalidIP = '127.0.0.';
        $validLocalIP = '127.0.0.1';
        $validLocalIP6 = '::1';
        $invalidIPv6 = '::1:::';
        $validLocalIP61 = '::0:1'; // equal ::1
        $validPrivateIP = '192.168.0.1';
        $this->assertNull(
            Addresses::filterIp($invalidIP),
            sprintf(
                'Addresses::filterIp("%1$s") should return null cause invalid',
                $invalidIP
            )
        );
        $this->assertNull(
            Addresses::filterIp($invalidIPv6),
            sprintf(
                'Addresses::filterIp("%1$s") should return null cause invalid',
                $invalidIPv6
            )
        );
        $this->assertSame(
            $validLocalIP,
            Addresses::filterIp($validLocalIP),
            sprintf(
                'Addresses::filterIp("%1$s") should return "%1$s"',
                $validLocalIP
            )
        );
        $this->assertSame(
            $validPrivateIP,
            Addresses::filterIp($validPrivateIP),
            sprintf(
                'Addresses::filterIp("%1$s") should return "%1$s"',
                $validLocalIP
            )
        );
        $this->assertSame(
            $validLocalIP6,
            Addresses::filterIp($validLocalIP6),
            sprintf(
                'Addresses::filterIp("%1$s") should return "%1$s"',
                $validLocalIP6
            )
        );
        $this->assertSame(
            $validLocalIP6,
            Addresses::filterIp($validLocalIP61),
            sprintf(
                'Addresses::filterIp("%1$s") should return "%2$s"',
                $validLocalIP61,
                $validLocalIP6
            )
        );
    }

    public function testFilterIpv4()
    {
        $invalidIP = '::1';
        $validLocalIP = '127.0.0.1';
        $this->assertNull(
            Addresses::filterIpv4($invalidIP),
            sprintf(
                'Addresses::filterIpv4("%1$s") should return null cause invalid ip4',
                $invalidIP
            )
        );
        $this->assertSame(
            $validLocalIP,
            Addresses::filterIpv4($validLocalIP),
            sprintf(
                'Addresses::filterIpv4("%1$s") should return "%1$s" ip4',
                $validLocalIP
            )
        );
    }

    public function testFilterIpv6()
    {
        $invalidIP = '127.0.0.1';
        $validLocalIP = '::1';
        $this->assertNull(
            Addresses::filterIpv6($invalidIP),
            sprintf(
                'Addresses::filterIpv6("%1$s") should return null cause invalid ip4',
                $invalidIP
            )
        );
        $this->assertSame(
            $validLocalIP,
            Addresses::filterIpv6($validLocalIP),
            sprintf(
                'Addresses::filterIpv6("%1$s") should return "%1$s" ip4',
                $validLocalIP
            )
        );
    }

    public function testReverseIp()
    {
        $invalidIP = '127.0.0.';
        $invalidIPv6 = '::1:::';
        $validLocalIP = '127.0.0.1';
        $validLocalIP6 = '::1';
        $ip4Arpa = implode(
            '.',
            array_reverse(explode('.', $validLocalIP))
        ) .'.in-addr.arpa';
        $ip6Arpa = implode(
            '.',
            array_reverse(explode(':', $validLocalIP6))
        ) .'.ip6.arpa';
        $this->assertNull(
            Addresses::reverseIp($invalidIP),
            sprintf(
                'Addresses::reverseIp("%1$s") should return null cause invalid ip',
                $invalidIP
            )
        );
        $this->assertNull(
            Addresses::reverseIp($invalidIPv6),
            sprintf(
                'Addresses::reverseIp("%1$s") should return null cause invalid ip',
                $invalidIPv6
            )
        );
        $this->assertSame(
            Addresses::reverseIp($validLocalIP),
            $ip4Arpa,
            sprintf(
                'Addresses::reverseIp("%1$s") should return "%2$s"',
                $validLocalIP,
                $ip4Arpa
            )
        );
        $this->assertSame(
            Addresses::reverseIp($validLocalIP6),
            $ip6Arpa,
            sprintf(
                'Addresses::reverseIp("%1$s") should return "%2$s"',
                $validLocalIP6,
                $ip6Arpa
            )
        );
    }

    public function testGuessDNSServer()
    {
        $invalidIP = '127.0.0.';
        $validIP = '127.0.0.1';
        $localhost = 'localhost';
        $localhostUpper = 'LOCALHOST';
        $invalidDomain = 'invalidDomain';
        $this->assertNull(
            Addresses::guessDNSServer($invalidIP),
            sprintf(
                'Addresses::guessDNSServer("%1$s") should return null cause invalid ip',
                $invalidIP
            )
        );
        $this->assertNull(
            Addresses::guessDNSServer($invalidDomain),
            sprintf(
                'Addresses::guessDNSServer("%1$s") should return null cause invalid ip',
                $invalidDomain
            )
        );

        $this->assertSame(
            Addresses::guessDNSServer($localhostUpper),
            $localhost,
            sprintf(
                'Addresses::guessDNSServer("%1$s") should return "%2$s"',
                $localhostUpper,
                $localhost
            )
        );
        $this->assertSame(
            Addresses::guessDNSServer($validIP),
            $validIP,
            sprintf(
                'Addresses::guessDNSServer("%1$s") should return "%1$s"',
                $validIP
            )
        );
    }
}
