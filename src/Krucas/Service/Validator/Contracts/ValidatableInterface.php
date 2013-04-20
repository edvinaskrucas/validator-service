<?php namespace Krucas\Service\Validator\Contracts;

interface ValidatableInterface
{
    /**
     * Returns array of validation rules.
     *
     * @return array
     */
    public function getValidationRules();

    /**
     * Returns array of values to be validated.
     *
     * @return array
     */
    public function getValidationValues();
}