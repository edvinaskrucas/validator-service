<?php namespace Krucas\Service\Validator\Facades;

use Illuminate\Support\Facades\Facade;

class ValidatorService extends Facade
{
    /**
     * Get the registered component.
     *
     * @return object
     */
    protected static function getFacadeAccessor()
    {
        return 'validationservice';
    }
}