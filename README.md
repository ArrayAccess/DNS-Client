# PHP DNS Client - DNS Resolver


Native Php DNS Client / Resolver implementation of [RFC 1035](https://datatracker.ietf.org/doc/html/rfc1035).
Support [PSR-6: Caching Interface](https://www.php-fig.org/psr/psr-6/).

## Requirements

- `Php 8.1` or later
- Function `focksopen` enabled & allowed outbond connection to port `53`
- Network Connectivity (_Absolutely!_)

## Installing

Currently, in development:

```bash
composer require arrayaccess/dns-client:dev-main 
```

## Note

The [RRTypes](src/ResourceRecord/RRTypes) not completed yet,
will use [RRDefault](src/ResourceRecord/RRTypes/RRDefault.php) as default.
