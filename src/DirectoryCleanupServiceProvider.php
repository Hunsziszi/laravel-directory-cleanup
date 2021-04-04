<?php

namespace Spatie\DirectoryCleanup;

use Illuminate\Support\ServiceProvider;

class DirectoryCleanupServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'laravel-directory-cleanup.php' => config_path('laravel-directory-cleanup.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'laravel-directory-cleanup.php', 'laravel-directory-cleanup');

        $this->app->bind('command.clean:directories', DirectoryCleanupCommand::class);

        $this->commands([
            'command.clean:directories',
        ]);
    }
}
