<?php

namespace AMSender;

use Illuminate\Support\ServiceProvider;

class AMSenderServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge the package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/am-sender.php',
            'am-sender'
        );

        // Register the AM-Sender service as a singleton
        $this->app->singleton('am-sender', function ($app) {
            return new AMSender();
        });

        // Register the alias
        $this->app->alias('am-sender', AMSender::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // Publish the configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/am-sender.php' => config_path('am-sender.php'),
            ], 'am-sender-config');

            // Optionally publish all files at once
            $this->publishes([
                __DIR__.'/../config/am-sender.php' => config_path('am-sender.php'),
            ], 'am-sender');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'am-sender',
            AMSender::class,
        ];
    }
}
