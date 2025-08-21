<?php

namespace Clarus\SecureChat\Providers;

use Illuminate\Support\ServiceProvider;

class SecureChatServiceProvider extends ServiceProvider
{
    protected $commands = [
        // 'Clarus\SecureChat\Console\Commands\GenerateChatMessageSeedData',
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Package config
        $this->publishes([
            __DIR__.'/../config/secure-chat.php' => config_path('secure-chat.php'),
        ], 'config');

        // Package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->register(EventServiceProvider::class);

        // Commands

        $this->commands($this->commands);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        include __DIR__.'/../Http/routes.php';
    }
}
