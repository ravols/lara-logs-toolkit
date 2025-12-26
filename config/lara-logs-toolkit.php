<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Composer Dump Autoload Log Channel
    |--------------------------------------------------------------------------
    |
    | The log channel where the composer dump-autoload notification will be
    | written. This should match one of the channels defined in your
    | application's logging configuration.
    |
    */

    'composer_dump_autoload_channel' => env('LARA_LOGS_TOOLKIT_COMPOSER_DUMP_AUTOLOAD_CHANNEL', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Log Analysis Comparison Cache TTL
    |--------------------------------------------------------------------------
    |
    | The cache time-to-live in seconds for storing log analysis comparison
    | results. Default is 600 seconds (10 minutes).
    |
    */

    'comparison_cache_ttl' => env('LARA_LOGS_TOOLKIT_COMPARISON_CACHE_TTL', 600),

];
