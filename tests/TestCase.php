<?php

namespace Ravols\LaraLogsToolkit\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Ravols\LaraLogsToolkit\LaraLogsToolkitServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaraLogsToolkitServiceProvider::class,
        ];
    }
}
