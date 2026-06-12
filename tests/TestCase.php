<?php

namespace Tests;

use Abather\LaravelPermissionGenerator\PermissionGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PermissionGeneratorServiceProvider::class,
        ];
    }
}
