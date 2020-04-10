<?php

namespace btrsco\WhoisParser\Facades;

use Illuminate\Support\Facades\Facade;

class WhoisParser extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'whoisparser';
    }
}
