<?php

namespace CodingSocks\MultipartOfMadness\Tests;

use CodingSocks\MultipartOfMadness\MultipartOfMadnessServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            MultipartOfMadnessServiceProvider::class,
        ];
    }
}