<?php

use Ravols\LaraLogsToolkit\LaraLogsToolkit;

if (! function_exists('lastError')) {
    function lastError(...$arguments)
    {
        return app(LaraLogsToolkit::class)->getLastError(...$arguments);
    }
}

if (! function_exists('lastRecord')) {
    function lastRecord(...$arguments)
    {
        return app(LaraLogsToolkit::class)->getLastRecord(...$arguments);
    }
}
