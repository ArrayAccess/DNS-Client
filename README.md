# PHP DNS Client - DNS Resolver


Native Php DNS Client / Resolver implementation of [RFC 1035](https://datatracker.ietf.org/doc/html/rfc1035).
Support [PSR-6: Caching Interface](https://www.php-fig.org/psr/psr-6/).

## Requirements

- `Php 8.1` or later
- Function `focksopen` enabled & allowed outbound connection to port `53`
- Network Connectivity (_Absolutely!_)

## Installing

Currently, in development:

```bash
composer require arrayaccess/dns-client
```

## Usage

```php
use ArrayAccess\DnsRecord\Cache\Adapter\Psr6CacheAdapter;
use ArrayAccess\DnsRecord\DnsServerStorage;
use ArrayAccess\DnsRecord\Resolver;

$cache = new Psr6CacheAdapter();
// $cache->setCacheItemPool($cacheProvider);
$dnsServer = DnsServerStorage::createDefault();
$resolver = new Resolver($dnsServer, $cache);

/**
 * Lookup Single 
 */
$useCache = true; // default to true
$timeout = 3.5; // 3.5 seconds
$response = $resolver->lookup('domainname.ext', 'A', 'IN', $timeout, $useCache);

/**
 * Enable Pseudo OPT 
 */
$resolver->setDnsSec(true);
$response = $resolver->lookup('domainname.ext', 'A', 'IN');
$answers = $response->getAnswers();
$records = $answers->getRecords();
// Filter "A" Address Only
$arrayA = $records->getFilteredType('A');

```

> IXFR & AXFR not yet implemented


## Note

The [RRTypes](src/ResourceRecord/RRTypes) not completed yet,
will use [RRDefault](src/ResourceRecord/RRTypes/RRDefault.php) as default.
