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
     * Returns array of attributes to be validated.
     *
     * @return array
     */
    public function getValidationAttributes();
}