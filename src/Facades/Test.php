<?php

namespace Supsign\LaravelMyfactorySoap\Facades;

use Illuminate\Support\Facades\Facade;

class Test extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'test';
    }
}
