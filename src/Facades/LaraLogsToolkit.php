<?php

namespace Ravols\LaraLogsToolkit\Facades;

use Illuminate\Support\Facades\Facade;
use Ravols\LaraLogsToolkit\LaraLogsToolkit as LaraLogsToolkitClass;

class LaraLogsToolkit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaraLogsToolkitClass::class;
    }
}
