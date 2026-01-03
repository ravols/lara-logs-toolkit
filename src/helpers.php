<?php

if (! function_exists('llt')) {
    function llt(): LaraLogsToolkit
    {
        return app(LaraLogsToolkit::class);
    }
}
