<?php

namespace beinmedia\payment;

use beinmedia\payment\Commands\CreateWebhookCommand;
use beinmedia\payment\Services\PaypalRecurring;
use beinmedia\payment\Services\TapGateway;
use beinmedia\payment\Services\MyFatoorahGateway;
use beinmedia\payment\Services\PaypalGateway;
use Illuminate\Support\ServiceProvider;

class paymentServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'beinmedia');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'beinmedia');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
        $this->publishes([
            __DIR__.'/../config/payment.php' => config_path('package.php'),
        ]);
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
    }




    protected $commands = [
        CreateWebhookCommand::class
    ];

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {

        $this->mergeConfigFrom(__DIR__.'/../config/paypal.php', 'paypal');

        // Register the service the package provides.
        $this->app->singleton('tap', function ($app) {
            return new TapGateway();
        });
        $this->app->singleton('myFatoorah', function ($app) {
            return new MyFatoorahGateway();
        });
        $this->app->singleton('paypal', function ($app) {
            return new PaypalGateway();
        });
        $this->app->singleton('paypalRecurring', function ($app) {
            return new PaypalRecurring();
        });

        $this->commands($this->commands);

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['payment'];
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
            //__DIR__.'/../config/payment.php' => config_path('payment.php'),
            __DIR__.'/../config/paypal.php' => config_path('paypal.php'),
        ], 'payment.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/beinmedia'),
        ], 'payment.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/beinmedia'),
        ], 'payment.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/beinmedia'),
        ], 'payment.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
