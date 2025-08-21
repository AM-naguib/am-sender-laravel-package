<?php

namespace AMSender\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array listDevices()
 * @method static array createDevice(string $name)
 * @method static array send(array $payload)
 */
class AMSender extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'am-sender';
    }
}
