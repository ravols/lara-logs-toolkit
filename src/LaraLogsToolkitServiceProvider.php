<?php

namespace Ravols\LaraLogsToolkit;

use Illuminate\Support\ServiceProvider;
use Ravols\LaraLogsToolkit\Commands\CheckLogRecordsCommand;
use Ravols\LaraLogsToolkit\Commands\DeleteLogRecordsCommand;
use Ravols\LaraLogsToolkit\Commands\LogComposerDumpAutoloadCommand;

class LaraLogsToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/lara-logs-toolkit.php',
            'lara-logs-toolkit'
        );

        $this->app->singleton(LaraLogsToolkit::class, function () {
            return new LaraLogsToolkit();
        });

        $this->app->alias(LaraLogsToolkit::class, 'lara-logs-toolkit');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/lara-logs-toolkit.php' => config_path('lara-logs-toolkit.php'),
            ], 'lara-logs-toolkit-config');

            $this->commands([
                LogComposerDumpAutoloadCommand::class,
                CheckLogRecordsCommand::class,
                DeleteLogRecordsCommand::class,
            ]);
        }
    }
}
