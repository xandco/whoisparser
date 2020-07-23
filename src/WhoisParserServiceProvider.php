<?php

namespace xandco\WhoisParser;

use Illuminate\Support\ServiceProvider;

class WhoisParserServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'btrsco');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'btrsco');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/whoisparser.php', 'whoisparser');

        // Register the service the package provides.
        $this->app->singleton('whoisparser', function ($app) {
            return new WhoisParser;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['whoisparser'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/whoisparser.php' => config_path('whoisparser.php'),
        ], 'whoisparser.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/btrsco'),
        ], 'whoisparser.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/btrsco'),
        ], 'whoisparser.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/btrsco'),
        ], 'whoisparser.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
