# Whois Parser

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![License][ico-license]][link-license]

Whois Parser simply parses raw root or whois data that you supply.

During the parsing process, the package will do a few checks via regular expressions, to determine that the input data is workable as well as determine if the input contains errors or if you've been rate limited. Then the package will clean the input data and format each line into key value pairs. Once that has been completed it will start to iterate through and parse the data into a more readable form and into a reliable output based on your output format preference. 

## Installation

Install this package via composer:

``` bash
$ composer require xandco/whoisparser
```

This service provider must be installed (if using anything below Laravel 5.5)

``` php
// config/app.php

'providers' => [
    WhoisParser\WhoisParserServiceProvider::class,
];
```

Publish and customize configuration file with:

``` bash
$ php artisan vendor:publish --provider="WhoisParser\WhoisParserServiceProvider"
```

## Usage

Create new `WhoisParser` object:

``` php
use WhoisParser\WhoisParser;
...
$whoisParser = new WhoisParser( $options = [] );
```

You will need to get the whois data for whatever domain you'd like with an external method, WhoisParser just parses said data. Once you have your raw whois data you can call the `parse()` method to parse the data:

``` php
$whoisParser->parse( '% IANA WHOIS server\n...' ); // root data
$whoisParser->parse( 'Domain Name: EXAMPLE.COM\n...' ); // whois data
```

Based on whether you provide root data or whois data, the package will return differently structured objects. You will be able to check the type of object returned based on the `type` key containing either the value `root` or `whois`.

Here is an example of both outputs:

``` php
/*
 * Example of the `root` type output
 */
[
    'domain' => 'com',
    'is_valid' => true,
    'type' => 'root',
    'data' => [
        'status' => 'active',
        'whois' => 'whois.example.com',
        'contacts' => [
            'sponsor' => [
                'organisation' => 'Example Global Registry Services',
                'address' => [
                    '12345 Example Way',
                    'San Francisco California 94112',
                    'United States'
                ]
            ],
            'administrative' => [...],
            'technical' => [...],
        ],
        'nameservers' => [
            [
                'host' => 'ns.example.com',
                'ipv4' => '127.0.0.0',
                'ipv6' => '::1'
            ],
            ...
        ],
        'dates' => [
            'created' => '1985-01-01 00:00:00',
            'updated' => '2017-10-05 00:00:00',
        ]
    ],
    'raw' => '% IANA WHOIS server\n...' // depending on options
]

/*
 * Example of the `whois` type output
 */
[
    'domain' => 'example.com',
    'is_valid' => true,
    'is_reserved' => false,
    'is_available' => false,
    'type' => 'whois',
    'data' => [
        '_id' => '0897654321_DOMAIN_COM-EXPL',
        'status' => [
            [
                'code' => 'clientUpdateProhibited',
                'url' => 'https:\/\/www.icann.org\/epp#clientUpdateProhibited'
            ],
            ...
        ],
        'whois' => 'whois.example.com',
        'registrar' => [
            '_id' => '123',
            'name' => 'Example, Inc.',
            'whois' => 'whois.example.com',
            'abuse_contact' => [
                'email' => 'abuse@example.com',
                'phone' => '+1.4003219876',
            ],
        ],
        'contacts' => [
            'registrant' => [
                'name' => 'Domain Administrator',
                'organization' => 'Example Corporation',
                'address' => {
                   'street' => '12345 Example Way',
                   'city' => 'San Francisco',
                   'state_province' => 'CA',
                   'postal_code' => '94112',
                   'country' => 'US'
                 },
                'phone' => '+1.0981237645',
                'phone_ext' => null,
                'fax' => '+1.1230984567',
                'fax_ext' => null,
                'email' => 'admin@example.com',
            ],
            'administrative' => [...],
            'technical' => [...],
            'billing' => [...], // depending on options
        ],
        'nameservers' => [
            [
                'host' => 'ns.example.com',
                'ipv4' => null, // depending on options
                'ipv6' => null // depending on options
            ],
            ...
        ],
        'dnssec' => 'unsigned',
        'dates' => [
            'created' => '1991-05-01 21:00:00',
            'updated' => '2020-06-03 13:24:15',
            'expiration' => '2021-05-02 21:00:00',
        ]
    ],
    'raw' => 'Domain Name: EXAMPLE.COM\n...' // depending on options
]
```

### Options

When creating the `WhoisParser` object, there is only one `array` parameter that can be passed, which is *optional*.

Options array parameters:

| Option            | Notes                                                | Type     | Default  |
|-------------------|------------------------------------------------------|----------|----------|
| `output_format`   | options (`object`, `array`, `json`, `serialize`)     | `string` | `object` |
| `get_nserver_ip`  | try getting nameserver ips (using `gethostbyname()`) | `bool`   | `false`  |
| `parse_billing`   | try parsing billing contact (not always available)   | `bool`   | `false`  |
| `return_raw_data` | debug option, return raw input                       | `bool`   | `false`  |

Instead of setting these options when creating the object, you can alternatively set these globally in the configuration file, as well as modify the regex patterns used to determine things like: invalid, rate limited whois responses and reserved, taken, available domains. You can publish the configuration and customize it as shown in the [Installation](#installation) section.

## Changelog

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email [hello@xand.co](mailto:hello@xand.co) instead of using the issue tracker.

## Credits

- [X&Co][link-company]
- [Miguel Batres][link-author]
- [All Contributors][link-contributors]

## License

MIT - Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/xandco/whoisparser.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/xandco/whoisparser.svg?style=flat-square
[ico-license]: https://img.shields.io/packagist/l/xandco/whoisparser?style=flat-square

[link-packagist]: https://packagist.org/packages/xandco/whoisparser
[link-downloads]: https://packagist.org/packages/xandco/whoisparser
[link-author]: https://github.com/btrsco
[link-company]: https://github.com/xandco
[link-license]: https://github.com/xandco/whoisparser/blob/master/license.md
[link-contributors]: ../../contributors
