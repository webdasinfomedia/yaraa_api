<?php

namespace App\Facades;

/** 
 * Custom Facade
 */
class FcmDynamicLink
{
    protected static function resolveFacade($name)
    {
        return app()[$name]; /* or you can use $app->make($name) */
    }

    public static function __callStatic($method, $arguments)
    {
        return (self::resolveFacade('FcmDynamicLink'))->$method(...$arguments);
    }
}