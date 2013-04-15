<?php namespace Krucas\Service\Validator;

use Krucas\Service\Validator\Contracts\ValidatableInterface;
use Krucas\Service\Validator\Validator;
use Illuminate\Validation\Factory as IlluminateValidationFactory;

class Factory
{
    /**
     * Illuminate validation factory instance.
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $factory;

    /**
     * Creates new factory.
     *
     * @param \Illuminate\Validation\Factory $factory
     */
    public function __construct(IlluminateValidationFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Creates new validator service.
     *
     * @param ValidatableInterface $validatable
     * @return Validator
     */
    public function make(ValidatableInterface $validatable)
    {
        return new Validator($this->factory, $validatable);
    }

    /**
     * Returns illuminate validator factory instance.
     *
     * @return \Illuminate\Validation\Factory
     */
    public function getIlluminateValidationFactory()
    {
        return $this->factory;
    }
}