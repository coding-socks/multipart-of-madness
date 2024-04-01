<?php

namespace CodingSocks\MultipartOfMadness;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MultipartOfMadnessServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::group($this->routesConfigurations(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/multipart-of-madness.php', 'multipart-of-madness');

        $this->app->singleton(MultipartOfMadness::class, function ($app) {
            /** @var FilesystemManager $manager */
            $manager = $app->make(FilesystemManager::class);
            $disk = config('multipart-of-madness.storage_disk', 's3');
            return MultipartOfMadness::fromAdapter($manager->disk($disk));
        });

        $this->app->singleton('multipart-of-madness', function ($app) {
            return $app->make(MultipartOfMadness::class);
        });
    }

    /**
     * Routes configurations.
     *
     * @return array
     */
    private function routesConfigurations()
    {
        return [
            'as' => config('multipart-of-madness.routes.name', 'messenger.'),
            'prefix' => config('multipart-of-madness.routes.prefix'),
            'namespace' =>  config('multipart-of-madness.routes.namespace'),
            'middleware' => config('multipart-of-madness.routes.middleware'),
        ];
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'multipart-of-madness',
        ];
    }
}