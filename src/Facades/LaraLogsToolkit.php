<?php

namespace Slovar\LaraLogsToolkit\Facades;

use Illuminate\Support\Facades\Facade;
use Slovar\LaraLogsToolkit\LaraLogsToolkit as LaraLogsToolkitClass;

class LaraLogsToolkit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaraLogsToolkitClass::class;
    }
}
