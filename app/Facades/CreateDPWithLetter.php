<?php

namespace App\Facades;

/** 
 * Custom Facade
 */
class CreateDPWithLetter
{
    protected static function resolveFacade($name)
    {
        return app()[$name];
    }

    public static function __callStatic($method, $arguments)
    {
        return (self::resolveFacade('CreateDPWithLetter'))->$method(...$arguments);
    }
}