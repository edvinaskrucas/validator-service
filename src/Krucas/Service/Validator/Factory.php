<?php namespace Krucas\Service\Validator;

use Krucas\Service\Validator\Contracts\ValidatableInterface;
use Krucas\Service\Validator\Validator;
use Symfony\Component\Translation\TranslatorInterface;

class Factory
{
    /**
     * Translator interface implementation.
     *
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * Creates new factory.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Creates new validator service.
     *
     * @param ValidatableInterface $validatable
     * @return Validator
     */
    public function make(ValidatableInterface $validatable)
    {
        return new Validator($this->translator, $validatable);
    }

    /**
     * Returns translator instance.
     *
     * @return \Symfony\Component\Translation\TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }
}