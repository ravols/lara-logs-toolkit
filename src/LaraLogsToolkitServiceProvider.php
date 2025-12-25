<?php

namespace Slovar\LaraLogsToolkit;

use Illuminate\Support\ServiceProvider;
use Slovar\LaraLogsToolkit\Commands\LogComposerDumpAutoloadCommand;

class LaraLogsToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/lara-logs-toolkit.php',
            'lara-logs-toolkit'
        );

        $this->app->singleton(LaraLogsToolkit::class, function () {
            return new LaraLogsToolkit();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lara-logs-toolkit.php' => config_path('lara-logs-toolkit.php'),
            ], 'lara-logs-toolkit-config');

            $this->commands([
                LogComposerDumpAutoloadCommand::class,
            ]);
        }
    }
}
