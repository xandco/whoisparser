<?php

namespace WhoisParser;

use Carbon\Carbon;
use WhoisParser\Exceptions\WhoisParserException;
use WhoisParser\Exceptions\WhoisParserRateLimitedException;

class WhoisParser
{
    /**
     * Output Format String ('object', 'array', 'json', 'serialize')
     *
     * @var string
     */
    protected $_outputFormat;

    /**
     * Whether to get Nameserver host IP
     *
     * @var bool
     */
    protected $_getHostIps;

    /**
     * Whether to attempt parsing billing contact
     *
     * @var bool
     */
    protected $_parseBilling;

    /**
     * Whether to return raw whois data
     *
     * @var bool
     */
    protected $_returnRawData;

    /**
     * WhoisParser constructor.
     *
     * @param array $options
     */
    public function __construct( $options = [] )
    {
        $this->setOutputFormat( $options['output_format'] ?? config('whoisparser.output_format') );
        $this->setTryHostIps( $options['get_nserver_ip'] ?? config('whoisparser.get_nserver_ip') );
        $this->setParseBilling( $options['parse_billing'] ?? config('whoisparser.parse_billing') );
        $this->setReturnRawData( $options['return_raw_data'] ?? config('whoisparser.return_raw_data') );
    }

    /**
     * Get Output Format String
     *
     * @return mixed
     */
    protected function getOutputFormat()
    {
        return $this->_outputFormat;
    }

    /**
     * Set Output Format String
     *
     * @param mixed $outputFormat
     */
    protected function setOutputFormat( $outputFormat ): void
    {
        $this->_outputFormat = $outputFormat;
    }

    /**
     * Whether to Get Nameserver IPs
     *
     * @return bool
     */
    public function tryHostIps(): bool
    {
        return $this->_getHostIps;
    }

    /**
     * Set Get Nameserver Host Ips
     *
     * @param bool $getHostIps
     */
    public function setTryHostIps( bool $getHostIps ): void
    {
        $this->_getHostIps = $getHostIps;
    }

    /**
     * Whether to Parse Billing Contact if Available
     *
     * @return bool
     */
    public function parseBilling(): bool
    {
        return $this->_parseBilling;
    }

    /**
     * Set Parse Billing Contact Details
     *
     * @param bool $parseBilling
     */
    public function setParseBilling( bool $parseBilling ): void
    {
        $this->_parseBilling = $parseBilling;
    }

    /**
     * Whether to Return Raw Whois Data in Output
     *
     * @return bool
     */
    public function returnRawData(): bool
    {
        return $this->_returnRawData;
    }

    /**
     * Set Return Raw Whois Data
     *
     * @param bool $returnRawData
     */
    public function setReturnRawData( bool $returnRawData ): void
    {
        $this->_returnRawData = $returnRawData;
    }

    /**
     * Format Data for Output
     *
     * @param $output
     * @return false|mixed|string
     */
    protected function formatOutput( $output )
    {
        switch ( $this->getOutputFormat() ) {
            case 'array':
                return json_decode( json_encode( $output ), true );
            case 'json':
                return json_encode( $output );
            case 'serialize':
                return serialize( json_decode( json_encode( $output ), true ) );
            default:
                return json_decode( json_encode( $output ), false );
        }
    }

    /**
     * Check if String Starts with Substring
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function _startsWith( $haystack, $needle )
    {
        return substr( $haystack, 0, strlen( $needle ) ) === $needle;
    }

    /**
     * Check if String Ends with Substring
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function _endsWith( $haystack, $needle )
    {
        $length = strlen( $needle );
        if ( $length === 0 ) return true;
        return ( substr( $haystack, -$length ) === $needle );
    }

    /**
     * Check if String Contains Substring
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function _stringContains( $haystack, $needle )
    {
        return strpos( $haystack, $needle ) !== false;
    }

    /**
     * Converts string with lines to array
     *
     * @param $data
     * @return array
     */
    protected function _linesToArray( $data )
    {
        $array = explode( "\n", $data );
        foreach ( $array as $key => $value ) $array[$key] = trim( $value );

        return $array;
    }

    /**
     * Group array based on empty items
     *
     * @param $data
     * @return array
     */
    protected function _linesToGroups( $data )
    {
        $buffer = [];
        $index  = 0;
        $array  = [];

        foreach ( $data as $key => $item ) {
            if ( $item !== '' ) {
                array_push( $buffer, $item );
            } else {
                $array[$index] = $buffer;
                $buffer = [];
                $index++;
            }
        }

        return $array;
    }

    /**
     * Remove array items if they start with needle
     *
     * @param $haystack
     * @param $needle
     * @return array
     */
    protected function _arrayRemoveStartsWith( $haystack, $needle )
    {
        foreach ( $haystack as $key => $item ) {
            if ( $this->_startsWith( $item, $needle ) ) unset( $haystack[$key] );
        }

        return $haystack;
    }

    /**
     * Remove array items if they are empty
     *
     * @param $data
     * @return array
     */
    protected function _arrayRemoveEmpty( $data )
    {
        foreach ( $data as $key => $group ) {
            if ( empty( $group ) ) unset( $data[$key] );
        }

        return $data;
    }

    /**
     * Determine the IP version
     *
     * @param $data
     * @return int|null
     */
    protected function _getIpVersion( $data )
    {
        if ( filter_var( $data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) return 4;
        if ( filter_var( $data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) return 6;
        return null;
    }

    /**
     * Return supplied value if not empty/null
     *
     * @param $data
     * @return mixed|null
     */
    protected function _nullOrValue( $data )
    {
        if ( empty( $data ) ) return null;
        return $data;
    }

    /**
     * Return format contact info
     *
     * @param $data
     * @return array
     */
    protected function _formatContactInfo( $data )
    {
        return [
            'name'         => isset( $data['name'] ) ? $this->_nullOrValue( $data['name'] ) : null,
            'organization' => isset( $data['organization'] ) ? $this->_nullOrValue( $data['organization'] ) : null,
            'address'      => [
                'street'         => isset( $data['street'] ) ? $this->_nullOrValue( $data['street'] ) : null,
                'city'           => isset( $data['city'] ) ? $this->_nullOrValue( $data['city'] ) : null,
                'state_province' => isset( $data['state_province'] ) ? $this->_nullOrValue( $data['state_province'] ) : null,
                'postal_code'    => isset( $data['postal_code'] ) ? $this->_nullOrValue( $data['postal_code'] ) : null,
                'country'        => isset( $data['country'] ) ? $this->_nullOrValue( $data['country'] ) : null,
            ],
            'phone'        => isset( $data['phone'] ) ? $this->_nullOrValue( $data['phone'] ) : null,
            'phone_ext'    => isset( $data['phone_ext'] ) ? $this->_nullOrValue( $data['phone_ext'] ) : null,
            'fax'          => isset( $data['fax'] ) ? $this->_nullOrValue( $data['fax'] ) : null,
            'fax_ext'      => isset( $data['fax_ext'] ) ? $this->_nullOrValue( $data['fax_ext'] ) : null,
            'email'        => isset( $data['email'] ) ? $this->_nullOrValue( $data['email'] ) : null,
        ];
    }

    /**
     * Checks if haystack contains pattern needles
     *
     * @param string $haystack
     * @param array $needles
     * @return bool|mixed
     */
    protected function _containsPattern( string $haystack, array $needles )
    {
        if ( !empty( $needles ) ) {
            foreach ( $needles as $needle ) {
                preg_match_all( "/^$needle\n/miU", $haystack, $matches, PREG_SET_ORDER, 0 );

                if ( !empty( $matches ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if domain is valid via whois data
     *
     * @param $rawData
     * @return bool
     */
    public function isDomainValid( $rawData )
    {
        return !$this->_containsPattern( $rawData, config( 'whoisparser.patterns.invalid' ) ?? [] );
    }

    /**
     * Determine if user has been rate limited via whois data
     *
     * @param $rawData
     * @return bool|mixed
     */
    public function isRateLimited( $rawData )
    {
        return $this->_containsPattern( $rawData, config( 'whoisparser.patterns.rate_limit' ) ?? [] );
    }

    /**
     * Determine if domain is reserved via whois data
     *
     * @param $rawData
     * @return bool
     */
    public function isReserved( $rawData )
    {
        if ( $this->_containsPattern( $rawData, config( 'whoisparser.patterns.reserved' ) ?? [] ) ) return true;
        return false;
    }

    /**
     * Determine if domain is available via whois data
     *
     * @param $rawData
     * @return bool
     */
    public function isAvailable( $rawData )
    {
        if ( $this->isReserved( $rawData ) ) return false;
        if ( $this->_containsPattern( $rawData, config( 'whoisparser.patterns.taken' ) ?? [] ) ) return false;
        if ( $this->_containsPattern( $rawData, config( 'whoisparser.patterns.available' ) ?? [] ) ) return true;
        return false;
    }

    /**
     * Parse Raw Root Database Data
     *
     * @param $rawData
     * @return array
     */
    public function parseRootData( $rawData )
    {
        $lines  = $this->_linesToArray( $rawData );
        $index  = 0;
        $groups = [
            'raw' => [],
            'formatted' => [],
            'parsed' => [
                'domain' => null,
                'is_valid' => null,
                'type' => 'root',
                'data' => [
                    'status' => null,
                    'whois' => null,
                    'contacts' => [
                        'sponsor' => null,
                        'administrative' => null,
                        'technical' => null,
                    ],
                    'nameservers' => [],
                    'dates' => [
                        'created' => null,
                        'updated' => null,
                    ]
                ]
            ],
        ];

        // Remove unnecessary comments
        $lines = $this->_arrayRemoveStartsWith( $lines, '%' );

        // Group all line separated sections
        $groups['raw'] = $this->_linesToGroups( $lines );

        // Remove empty arrays
        $groups['raw'] = $this->_arrayRemoveEmpty( $groups['raw'] );

        // Format raw group lines
        foreach ( $groups['raw'] as $key => $group ) {
            foreach ( $group as $groupKey => $line ) {
                preg_match_all( '/^(?<name>.*?):\s+(?<value>.*)/', $line, $matches, PREG_SET_ORDER, 0 );

                if ( empty( $matches ) ) continue;

                $name  = strtolower( $matches[0]['name'] );
                $value = trim( $matches[0]['value'] );

                if ( $name === 'address' || $name === 'nserver' || $name === 'remarks' || $name === 'ds-rdata' ) {
                    if ( !isset( $groups['formatted'][$index][$name] ) ) $groups['formatted'][$index][$name] = [];
                    array_push( $groups['formatted'][$index][$name], $value );
                } else {
                    $groups['formatted'][$index][$name] = $value;
                }
            }
            $index++;
        }

        // Determine if domain is valid
        $groups['parsed']['is_valid'] = $this->isDomainValid( $rawData );

        // Finalize group parsing
        foreach ( $groups['formatted'] as $key => $group ) {
            if ( isset( $group['domain'] ) )
                $groups['parsed']['domain'] = $this->_nullOrValue( strtolower( $group['domain'] ) );
            if ( isset( $group['status'] ) )
                $groups['parsed']['data']['status'] = $this->_nullOrValue( strtolower( $group['status'] ) );
            if ( isset( $group['whois'] ) )
                $groups['parsed']['data']['whois'] = $this->_nullOrValue( $group['whois'] );
            if ( isset( $group['created'] ) )
                $groups['parsed']['data']['dates']['created'] = Carbon::parse( $this->_nullOrValue( $group['created'] ) )->toDateTimeString();
            if ( isset( $group['changed'] ) )
                $groups['parsed']['data']['dates']['updated'] = Carbon::parse( $this->_nullOrValue( $group['changed'] ) )->toDateTimeString();

            if ( isset( $group['organisation'] ) ) {

                if ( isset( $group['contact'] ) ) {
                    if ( isset( $group['name'] ) )
                        $groups['parsed']['data']['contacts'][ $group['contact'] ]['name'] = $this->_nullOrValue( $group['name'] );
                    if ( isset( $group['organisation'] ) )
                        $groups['parsed']['data']['contacts'][ $group['contact'] ]['organisation'] = $this->_nullOrValue( $group['organisation'] );
                    if ( isset( $group['address'] ) )
                        $groups['parsed']['data']['contacts'][ $group['contact'] ]['address'] = $this->_nullOrValue( $group['address'] );
                    if ( isset( $group['phone'] ) )
                        $groups['parsed']['data']['contacts'][ $group['contact'] ]['phone'] = $this->_nullOrValue( $group['phone'] );
                    if ( isset( $group['fax-no'] ) )
                        $groups['parsed']['data']['contacts'][ $group['contact'] ]['fax'] = $this->_nullOrValue( $group['fax-no'] );
                    if ( isset( $group['e-mail'] ) )
                        $groups['parsed']['data']['contacts'][ $group['contact'] ]['email'] = $this->_nullOrValue( $group['e-mail'] );
                } else {
                    if ( isset( $group['organisation'] ) )
                        $groups['parsed']['data']['contacts']['sponsor']['organisation'] = $this->_nullOrValue( $group['organisation'] );
                    if ( isset( $group['address'] ) )
                        $groups['parsed']['data']['contacts']['sponsor']['address'] = $this->_nullOrValue( $group['address'] );
                }

            }

            if ( isset( $group['nserver'] ) ) {
                foreach ( $group['nserver'] as $nameservers ) {
                    preg_match_all( '/^(?<host>.*)\s+(?<ipv4>.*)\s+(?<ipv6>.*)/', $nameservers, $matches, PREG_SET_ORDER, 0 );

                    $nameserver = [
                        'host' => $this->_nullOrValue( strtolower( $matches[0]['host'] ) ),
                        'ipv4' => $this->_nullOrValue( trim( $matches[0]['ipv4'] ) ),
                        'ipv6' => $this->_nullOrValue( trim( $matches[0]['ipv6'] ) ),
                    ];

                    array_push( $groups['parsed']['data']['nameservers'], $nameserver );
                }
            }
        }

        if ( $this->returnRawData() ) $groups['parsed']['raw'] = $rawData;

        return $groups['parsed'];
    }

    /**
     * Parse Raw Whois Data
     *
     * @param $rawData
     * @return array
     */
    public function parseWhoisData( $rawData )
    {
        $lines  = $this->_linesToArray( $rawData );
        $index  = 0;
        $groups = [
            'raw' => [],
            'formatted' => [],
            'parsed' => [
                'domain' => null,
                'is_valid' => null,
                'is_reserved' => null,
                'is_available' => null,
                'type' => 'whois',
                'data' => [
                    '_id' => null,
                    'status' => [],
                    'whois' => null,
                    'registrar' => [
                        '_id' => null,
                        'name' => null,
                        'whois' => null,
                        'abuse_contact' => [
                            'email' => null,
                            'phone' => null,
                        ],
                    ],
                    'contacts' => [
                        'registrant' => null,
                        'administrative' => null,
                        'technical' => null,
                        'billing' => null,
                    ],
                    'nameservers' => [],
                    'dnssec' => null,
                    'dates' => [
                        'created' => null,
                        'updated' => null,
                        'expiration' => null,
                    ]
                ]
            ],
        ];

        // Remove unnecessary comments
        $lines = $this->_arrayRemoveStartsWith( $lines, '>' );

        // Group all line separated sections
        $groups['raw'] = $this->_linesToGroups( $lines );

        // Remove empty arrays
        $groups['raw'] = $this->_arrayRemoveEmpty( $groups['raw'] );

        // Format raw group lines
        foreach ( $groups['raw'] as $key => $group ) {
            foreach ( $group as $groupKey => $line ) {
                if ( $index == 0 ) {
                    if ( $this->_endsWith( $line, ':' ) ) $line = "$line ";

                    preg_match_all( '/^(?<name>.*?):\s+(?<value>.*)/', $line, $matches, PREG_SET_ORDER, 0 );

                    if ( empty( $matches ) ) continue;

                    $name  = strtolower( str_replace( ' ', '_', $matches[0]['name'] ) );
                    $name  = strtolower( str_replace( '/', '_', $name ) );
                    $name  = strtolower( str_replace( ':', '', $name ) );
                    $value = trim( $matches[0]['value'] );

                    if ( $name === 'domain_status' || $name === 'name_server' || $name === 'nserver' ) {
                        if ( !isset( $groups['formatted'][$index][$name] ) ) $groups['formatted'][$index][$name] = [];
                        array_push( $groups['formatted'][$index][$name], $value );
                    } else if ( $this->_startsWith( $name, 'registrant' ) ) {
                        if ( !isset( $groups['formatted'][$index]['registrant'] ) ) $groups['formatted'][$index]['registrant'] = [];
                        $keyName = str_replace( 'registrant_', '', $name );
                        $groups['formatted'][$index]['registrant'][$keyName] = empty( $value ) ? null : $value;
                    } else if ( $this->_startsWith( $name, 'admin' ) ) {
                        if ( !isset( $groups['formatted'][$index]['administrative'] ) ) $groups['formatted'][$index]['administrative'] = [];
                        $keyName = str_replace( 'administrative_', '', $name );
                        $keyName = str_replace( 'administration_', '', $keyName );
                        $keyName = str_replace( 'admin_', '', $keyName );
                        $groups['formatted'][$index]['administrative'][$keyName] = empty( $value ) ? null : $value;
                    } else if ( $this->_startsWith( $name, 'tech' ) ) {
                        if ( !isset( $groups['formatted'][$index]['technical'] ) ) $groups['formatted'][$index]['technical'] = [];
                        $keyName = str_replace( 'technical_', '', $name );
                        $keyName = str_replace( 'technology_', '', $keyName );
                        $keyName = str_replace( 'tech_', '', $keyName );
                        $groups['formatted'][$index]['technical'][$keyName] = empty( $value ) ? null : $value;
                    } else if ( $this->parseBilling() && $this->_startsWith( $name, 'billing' ) ) {
                        if ( !isset( $groups['formatted'][$index]['billing'] ) ) $groups['formatted'][$index]['billing'] = [];
                        $keyName = str_replace( 'billing_', '', $name );
                        $groups['formatted'][$index]['billing'][$keyName] = empty( $value ) ? null : $value;
                    } else {
                        $groups['formatted'][$index][$name] = $value;
                    }
                }
            }

            $index++;
        }

        // Determine if domain is available
        $groups['parsed']['is_valid']     = $this->isDomainValid( $rawData );
        $groups['parsed']['is_reserved']  = $this->isReserved( $rawData );
        $groups['parsed']['is_available'] = $this->isAvailable( $rawData );

        // Finalize group parsing
        foreach ( $groups['formatted'] as $key => $group ) {
            if ( isset( $group['domain_name'] ) )
                $groups['parsed']['domain'] = $this->_nullOrValue( strtolower( $group['domain_name'] ) );
            if ( isset( $group['dnssec'] ) )
                $groups['parsed']['data']['dnssec'] = $this->_nullOrValue( $group['dnssec'] );
            if ( isset( $group['registry_domain_id'] ) )
                $groups['parsed']['data']['_id'] = $this->_nullOrValue( $group['registry_domain_id'] );

            // Parse all dates
            if ( isset( $group['created_date'] ) )
                $groups['parsed']['data']['dates']['created'] = Carbon::parse( $this->_nullOrValue( $group['created_date'] ) )->toDateTimeString();
            if ( isset( $group['creation_date'] ) )
                $groups['parsed']['data']['dates']['created'] = Carbon::parse( $this->_nullOrValue( $group['creation_date'] ) )->toDateTimeString();
            if ( isset( $group['updated_date'] ) )
                $groups['parsed']['data']['dates']['updated'] = Carbon::parse( $this->_nullOrValue( $group['updated_date'] ) )->toDateTimeString();
            if ( isset( $group['registrar_registration_expiration_date'] ) )
                $groups['parsed']['data']['dates']['expiration'] = Carbon::parse( $this->_nullOrValue( $group['registrar_registration_expiration_date'] ) )->toDateTimeString();
            if ( isset( $group['registry_expiry_date'] ) )
                $groups['parsed']['data']['dates']['expiration'] = Carbon::parse( $this->_nullOrValue( $group['registry_expiry_date'] ) )->toDateTimeString();

            // Handle registrar details
            if ( isset( $group['registrar_iana_id'] ) )
                $groups['parsed']['data']['registrar']['_id'] = $this->_nullOrValue( $group['registrar_iana_id'] );
            if ( isset( $group['registrar'] ) )
                $groups['parsed']['data']['registrar']['name'] = $this->_nullOrValue( $group['registrar'] );
            if ( isset( $group['registrar_abuse_contact_email'] ) )
                $groups['parsed']['data']['registrar']['abuse_contact']['email'] = $this->_nullOrValue( $group['registrar_abuse_contact_email'] );
            if ( isset( $group['registrar_abuse_contact_phone'] ) )
                $groups['parsed']['data']['registrar']['abuse_contact']['phone'] = $this->_nullOrValue( $group['registrar_abuse_contact_phone'] );

            if ( isset( $group['registrar_whois_server'] ) ) {
                $groups['parsed']['data']['whois'] = $this->_nullOrValue( $group['registrar_whois_server'] );
                $groups['parsed']['data']['registrar']['whois'] = $this->_nullOrValue( $group['registrar_whois_server'] );
            }

            // Parse domain statuses
            if ( isset( $group['domain_status'] ) ) {
                foreach ( $group['domain_status'] as $status ) {
                    preg_match_all( '/^(?<code>.*?)\s+(?<url>.*)/', $status, $matches, PREG_SET_ORDER, 0 );

                    $code = trim( $matches[0]['code'] );
                    $url  = trim( $matches[0]['url'], "\t\n\r\0\x0B()" );

                    array_push( $groups['parsed']['data']['status'], [
                        'code' => $code,
                        'url' => $url,
                    ]);
                }
            }

            // Parse Domain Contacts
            $groups['parsed']['data']['contacts']['registrant'] = $this->_formatContactInfo( $group['registrant'] ?? null );
            $groups['parsed']['data']['contacts']['administrative'] = $this->_formatContactInfo( $group['administrative'] ?? null );
            $groups['parsed']['data']['contacts']['technical'] = $this->_formatContactInfo( $group['technical'] ?? null );

            // Parse Billing Contact
             if ( $this->parseBilling() )
                 $groups['parsed']['data']['contacts']['billing'] = $this->_formatContactInfo( $group['billing'] ?? null );

            // Parse name servers
            if ( isset( $group['name_server'] ) ) {
                foreach ( $group['name_server'] as $server ) {
                    if ( !empty( $server ) ) {
                        $host = strtolower( trim( $server ) );
                        $ip = $this->tryHostIps() ? gethostbyname( $host ) : null;

                        array_push( $groups['parsed']['data']['nameservers'], [
                            'host' =>  $host,
                            'ipv4' => !is_null( $ip ) && $this->_getIpVersion( $ip ) === 4 ? $ip : null,
                            'ipv6' => !is_null( $ip ) && $this->_getIpVersion( $ip ) === 6 ? $ip : null,
                        ]);
                    }
                }
            }
        }

        // Remove Billing Key (if unneeded)
        if ( !$this->parseBilling() ) unset( $groups['parsed']['data']['contacts']['billing'] );

        // Return Raw Whois Data (if wanted)
        if ( $this->returnRawData() ) $groups['parsed']['raw'] = $rawData;

        return $groups['parsed'];
    }

    /**
     * Return Parsed Whois from Raw Whois Data
     *
     * @param $rawData
     * @return array
     * @throws WhoisParserException
     */
    public function parse( $rawData )
    {
        if ( empty( trim( $rawData ) ) )
            throw new WhoisParserException( 'No whois data to parse' );

        if ( $this->isRateLimited( $rawData ) )
            throw new WhoisParserRateLimitedException( 'The whois server has limited you from making more queries' );

        // Determine if raw data is root data
        if ( $this->_startsWith( $rawData, '% IANA WHOIS server' ) )
            return $this->formatOutput( $this->parseRootData( $rawData ) );
        else
            return $this->formatOutput( $this->parseWhoisData( $rawData ) );

    }
}
