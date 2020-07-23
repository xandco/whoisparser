<?php

namespace xandco\WhoisParser;

class WhoisParser
{
    /**
     * Output Format String ('object', 'array', 'json', 'serialize')
     * @var $_outputFormat
     */
    protected $_outputFormat;

    /**
     * Raw string whois or root data
     * @var $_rawData
     */
    protected $_rawData;

    /**
     * WhoisParser constructor.
     * @param string $outputFormat
     */
    public function __construct( $outputFormat = 'object' )
    {
        $this->setOutputFormat( $outputFormat );
    }

    /**
     * Get Output Format String
     * @return mixed
     */
    protected function getOutputFormat()
    {
        return $this->_outputFormat;
    }

    /**
     * Set Output Format String
     * @param mixed $outputFormat
     */
    protected function setOutputFormat( $outputFormat ): void
    {
        $this->_outputFormat = $outputFormat;
    }

    /**
     * Set Raw Data String
     * @return mixed
     */
    public function getRawData()
    {
        return $this->_rawData;
    }

    /**
     * Get Raw Data String
     * @param mixed $rawData
     */
    public function setRawData( $rawData ): void
    {
        $this->_rawData = $rawData;
    }

    /**
     * Format Data for Output
     * @param $output
     * @return false|mixed|string
     */
    protected function formatOutput( $output )
    {
        switch ( $this->getOutputFormat() ) {
            case 'array':
                return json_decode( json_encode( $output ), true );
                break;
            case 'json':
                return json_encode( $output );
                break;
            case 'serialize':
                return serialize( json_decode( json_encode( $output ), true ) );
                break;
            default:
                return json_decode( json_encode( $output ), false );
                break;
        }
    }

    /**
     * Check if String Starts with Substring
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
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function _stringContains( $haystack, $needle )
    {
        return strpos( $haystack, $needle ) !== false;
    }

    protected function _linesToArray( $data )
    {
        $array = explode( "\n", $data );
        foreach ( $array as $key => $value ) $array[$key] = trim( $value );

        return $array;
    }

    /**
     * Parse Raw Root Database Data
     * @param $rawData
     * @return array
     */
    protected function parseRootData( $rawData )
    {
        $lines  = $this->_linesToArray( $rawData );
        $groups = [
            'raw' => [],
            'formatted' => [],
            'parsed' => [
                'domain' => null,
                'status' => null,
                'whois' => null,
                'contacts' => [
                    'sponsor' => null,
                    'administrative' => null,
                    'technical' => null,
                ],
                'nameservers' => [],
                'created' => null,
                'updated' => null,
            ],
        ];
        $buffer = [];
        $index  = 0;

        // Remove unnecessary comments
        foreach ( $lines as $key => $line ) {
            if ( $this->_startsWith( $line, '%' ) ) unset( $lines[$key] );
        }

        // Group all line separated sections
        foreach ( $lines as $key => $line ) {
            if ( $line !== '' ) {
                array_push( $buffer, $line );
            } else {
                $groups['raw'][$index] = $buffer;
                $buffer = [];
                $index++;
            }
        }

        $index  = 0;

        // Remove empty arrays
        foreach ( $groups['raw'] as $key => $group ) {
            if ( empty( $group ) ) unset( $groups['raw'][$key] );
        }

        // Format raw group lines
        foreach ( $groups['raw'] as $key => $group ) {
            foreach ( $group as $groupKey => $line ) {
                preg_match_all( '/^(?<name>.*?):\s+(?<value>.*)/', $line, $matches, PREG_SET_ORDER, 0 );
                $name  = strtolower( $matches[0]['name'] );
                $value = trim( $matches[0]['value'] );

                if ( $name === 'address' || $name === 'nserver' || $name === 'remarks' ) {
                    if ( !isset( $groups['formatted'][$index][$name] ) ) $groups['formatted'][$index][$name] = [];
                    array_push( $groups['formatted'][$index][$name], $value );
                } else {
                    $groups['formatted'][$index][$name] = $value;
                }
            }
            $index++;
        }

        // Finalize group parsing
        foreach ( $groups['formatted'] as $key => $group ) {
            if ( isset( $group['domain'] ) )   $groups['parsed']['domain']  = strtolower( $group['domain'] );
            if ( isset( $group['status'] ) )   $groups['parsed']['status']  = strtolower( $group['status'] );
            if ( isset( $group['whois'] ) )    $groups['parsed']['whois']   = $group['whois'];
            if ( isset( $group['created'] ) )  $groups['parsed']['created'] = $group['created'];
            if ( isset( $group['changed'] ) )  $groups['parsed']['updated'] = $group['changed'];

            if ( isset( $group['organisation'] ) ) {

                if ( isset( $group['contact'] ) ) {
                    $groups['parsed']['contacts'][ $group['contact'] ]['name'] = $group['name'];
                    $groups['parsed']['contacts'][ $group['contact'] ]['organisation'] = $group['organisation'];
                    $groups['parsed']['contacts'][ $group['contact'] ]['address'] = $group['address'];
                    $groups['parsed']['contacts'][ $group['contact'] ]['phone'] = $group['phone'];
                    $groups['parsed']['contacts'][ $group['contact'] ]['fax'] = $group['fax-no'];
                    $groups['parsed']['contacts'][ $group['contact'] ]['email'] = $group['e-mail'];
                } else {
                    $groups['parsed']['contacts']['sponsor']['organisation'] = $group['organisation'];
                    $groups['parsed']['contacts']['sponsor']['address'] = $group['address'];
                }

            }

            if ( isset( $group['nserver'] ) ) {
                foreach ( $group['nserver'] as $nameservers ) {
                    preg_match_all( '/^(?<host>.*)\s+(?<ipv4>.*)\s+(?<ipv6>.*)/', $nameservers, $matches, PREG_SET_ORDER, 0 );
                    $nameserver['host'] = strtolower( $matches[0]['host'] );
                    $nameserver['ipv4'] = trim( $matches[0]['ipv4'] );
                    $nameserver['ipv6'] = trim( $matches[0]['ipv6'] );

                    array_push( $groups['parsed']['nameservers'], $nameserver );
                }
            }
        }

        return $groups['parsed'];
    }

    /**
     * Parse Raw Whois Data
     */
    protected function parseWhoisData()
    {
        return 'whois';
    }

    /**
     * Return Parsed Whois from Raw Whois Data
     * @param string $rawData
     */
    public function parse( $rawData )
    {
        // Determine if raw data is root data
        if ( $this->_startsWith( $rawData, '% IANA WHOIS server' ) ){
            return $this->parseRootData( $rawData );
        } else {
            return $this->parseWhoisData();
        }

    }
}
