<?php

return [
    'output_format' => 'object', // Options: object, array, json, serialize
    'get_nserver_ip' => false,
    'parse_billing' => false,
    'return_raw_data' => false,

    /*
    |--------------------------------------------------------------------------
    | Whois Raw Data Patterns
    |--------------------------------------------------------------------------
    |
    | These options control how the parser will determine whether you have been
    | rate limited, if the domain is available, taken or reserved or others.
    |
    | If you encounter any responses from official whois servers that require
    | new patterns to be added please create a new issue requesting a pattern
    | to be added to the package.
    |
    | As a work around in the meantime, just create a pattern below and test it
    | with these regex these options: "/\n$pattern\n/miU"
    |
    */
    'patterns' => [
        'rate_limit' => [
            'has exceeded the established limit for.*\n.*queries',
            'number of allowed queries exceeded',
        ],
        'invalid' => [
            '% this query returned 0 object',
            '% you queried for .* but this server does not have',
            '%error.*102.* invalid domain name',
            '%error.*350.* invalid query syntax',
            '-7: %invalid pattern',
            'domain : syntax error in specified domain name',
            'error.',
            'invalid input',
            'parameter value syntax error',
            'please see the following query examples and try again.*',
            'requests of this client are not permitted',
            'required syntax: \^\(\?:\[a-z0-9\]\(\?:\[a-z0-9\\-\]\(\?!\\\.\)\)\{0,61\}\[a-z0-9\]\?\\\.\)\+\(dz\)\$',
            'the query type is incorrect.*',
            'wrong top level domain name in query',
            '網域名稱不合規定',
        ],
        'reserved' => [
            'domain .* has been reserved.*',
            'reserved by.*',
            'the registration of this domain is restricted.*',
        ],
        'taken' => [
            '% this query returned 1 object.*',
            '.*creation date: .*',
            '.*domain status: client.* https:\/\/icann.org\/.*',
            '.*name server: .*',
            '.*registrar abuse contact .*: .*',
            '.*registry .* id: .*',
            '.*registry expiry date: .*',
            '.*registry whois server: .*',
            '.*updated date: .*',
        ],
        'available' => [
            '% no entries found.*',
            '% no match.*',
            '% no such domain',
            '% not registered.*',
            '% nothing found',
            '%% no entries found.*',
            '%error: no entries found',
            '%error:101: no entries found',
            '%error:103: domain is not registered',
            '.* - no match',
            '.* is free',
            '.*: no entries found.*',
            '.*domain not found.*',
            '.*no match for.*',
            '.*this domain name has not been registered.*',
            '>>> domain .* is available for registration',
            '\*\*\* nothing found for this query.*',
            'available',
            'data not found.*',
            'domain .* is available.*',
            'domain .* not found.*',
            'domain status: free',
            'domain status: no object found',
            'domain unknown',
            'el dominio no se encuentra registrado.*',
            'invalid query or domain name not known.*',
            'nincs talalat \/ no match',
            'no data found.*',
            'no entries found for the selected source.*',
            'no entries found.*',
            'no found.*',
            'no information available about domain name.*',
            'no information was found.*',
            'no match.*',
            'no object found.*',
            'no such domain.*',
            'no_se_encontro_el_objeto\/object_not_found',
            'not find matchingrecord',
            'not found.*',
            'not registered.*',
            'nothing found.*',
            'object does not exist.*',
            'query_status: 220 available',
            'registration status: available',
            'sorry, but domain: .*, not found in database',
            'status:.*available',
            'status:.*free',
            'the domain .* was not found.*',
            'the domain has not been registered.*',
            'the queried object does not exist.*',
            'the requested domain was not found.*',
            'this domain name has not been registered.*',
        ]
    ]
];
