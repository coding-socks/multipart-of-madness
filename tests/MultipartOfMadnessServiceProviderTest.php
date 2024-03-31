<?php

namespace CodingSocks\MultipartOfMadness\Tests;

use Carbon\CarbonInterval;
use CodingSocks\MultipartOfMadness\MultipartOfMadness;

class MultipartOfMadnessServiceProviderTest extends TestCase
{
    public function testMake()
    {
        $this->app->make('config')->set('filesystems.disks.s3', [
            'driver' => 's3',
            'key' => 'TEST_AWS_ACCESS_KEY_ID',
            'secret' => 'TEST_AWS_SECRET_ACCESS_KEY',
            'region' => 'TEST_AWS_DEFAULT_REGION',
            'bucket' => 'TEST_AWS_BUCKET',
            'url' => 'TEST_AWS_URL',
            'endpoint' => 'http://TEST_AWS_ENDPOINT',
            'use_path_style_endpoint' => false,
            'throw' => false,
        ]);

        $instance = $this->app->make('multipart-of-madness');

        $this->assertInstanceOf(MultipartOfMadness::class, $instance);
    }

    public function testDefaultConfig()
    {
        $config = $this->app->make('config')->get('multipart-of-madness');

        $this->assertArrayHasKey('expiration_time', $config);
        $this->assertEquals(900, CarbonInterval::make($config['expiration_time'])->totalSeconds);

        $this->assertArrayHasKey('storage_disk', $config);
        $this->assertEquals('s3', $config['storage_disk']);

        $this->assertArrayHasKey('routes', $config);
        $this->assertEquals([
            'name' => 'uppy.',
            'prefix' => 'uppy',
            'middleware' => ['web', 'auth'],
            'namespace' => 'CodingSocks\MultipartOfMadness\Http\Controllers',
        ], $config['routes']);
    }
}