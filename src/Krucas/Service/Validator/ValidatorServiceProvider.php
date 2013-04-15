<?php namespace Krucas\Service\Validator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory;

class ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('edvinaskrucas/validator-service');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['service.validator'] = $this->app->bind(function($app, $params = array())
        {
            return new Validator(new Factory($app['translator']), $params[0]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}