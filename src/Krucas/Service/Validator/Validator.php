<?php namespace Krucas\Service\Validator;

use Illuminate\Events\Dispatcher;
use Illuminate\Validation\Factory as IlluminateValidationFactory;
use Krucas\Service\Validator\Contracts\ValidatableInterface;

class Validator
{
    /**
     * Validation error messages, or null if no messages.
     *
     * @var \Illuminate\Support\MessageBag|null
     */
    protected $errors;

    /**
     * Validatable object instance.
     *
     * @var \Krucas\Service\Validator\Contracts\ValidatableInterface
     */
    protected $validatable;

    /**
     * Rules to check against.
     *
     * @var array
     */
    protected $rules = array();

    /**
     * Attributes to check against rules.
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Validation factory to make a new Illuminate Validator instance.
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $factory;

    /**
     * Event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher|null
     */
    protected static $dispatcher = null;

    /**
     * Creates new validator service.
     *
     * @param IlluminateValidationFactory $factory
     * @param \Krucas\Service\Validator\Contracts\ValidatableInterface $validatable
     */
    public function __construct(IlluminateValidationFactory $factory, ValidatableInterface $validatable)
    {
        $this->factory      = $factory;
        $this->validatable  = $validatable;

        $this->attributes   = $validatable->getValidationAttributes();
        $this->rules        = $validatable->getValidationRules();
    }

    /**
     * Validates object and fires events.
     *
     * @return bool
     */
    protected function validate()
    {
        if($this->fireEvent('validating') === false) return false;

        $validator = $this->factory->make($this->getAttributes(), $this->getRules());

        $passed = $validator->passes();

        if(!$passed)
        {
            $this->errors = $validator->errors();
        }
        else
        {
            $this->fireEvent('validated', false);
        }

        return $passed;
    }

    /**
     * Method which fires given events.
     *
     * @param string $event
     * @param bool $halt
     * @return bool
     */
    protected function fireEvent($event, $halt = true)
    {
        if(!isset(static::$dispatcher)) return true;

        $globalEvent = "service.validator.{$event}";
        $event = $globalEvent.": ".get_class($this->validatable);

        $method = $halt ? 'until' : 'fire';

        if(static::$dispatcher->$method($globalEvent, $this) === false) return false;

        return static::$dispatcher->$method($event, $this);
    }

    /**
     * Validates attributes against rules and returns result.
     *
     * @return bool
     */
    public function passes()
    {
        return $this->validate();
    }

    /**
     * Returns errors.
     *
     * @return \Illuminate\Support\MessageBag|null
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns attributes keys with its values.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Sets validation rules.
     *
     * @param array $rules
     * @return \Krucas\Service\Validator\Validator
     */
    public function setRules(array $rules = array())
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Sets attributes and its values.
     *
     * @param array $attributes
     * @return \Krucas\Service\Validator\Validator
     */
    public function setAttributes(array $attributes = array())
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Sets rules of a given attribute.
     *
     * @param string $attribute
     * @param string $rules
     * @return \Krucas\Service\Validator\Validator
     */
    public function setAttributeRules($attribute, $rules)
    {
        $this->rules[$attribute] = $rules;

        return $this;
    }

    /**
     * Returns rules for a given attribute.
     *
     * @param $attribute
     * @return string
     */
    public function getAttributeRules($attribute)
    {
        return $this->rules[$attribute];
    }

    /**
     * Returns value of a given attribute.
     *
     * @param $attribute
     * @return mixed
     */
    public function getAttributeValue($attribute)
    {
        return $this->attributes[$attribute];
    }

    /**
     * Sets attribute value.
     *
     * @param string $attribute
     * @param mixed $value
     * @return \Krucas\Service\Validator\Validator
     */
    public function setAttributeValue($attribute, $value)
    {
        $this->attributes[$attribute] = $value;

        return $this;
    }

    /**
     * Removes attribute value and rules.
     *
     * @param string $attribute
     * @return \Krucas\Service\Validator\Validator
     */
    public function removeAttribute($attribute)
    {
        if(isset($this->attributes[$attribute]))
        {
            unset($this->attributes[$attribute]);
        }

        if(isset($this->rules[$attribute]))
        {
            unset($this->rules[$attribute]);
        }

        return $this;
    }

    /**
     * Returns validatable object.
     *
     * @return \Krucas\Service\Validator\Contracts\ValidatableInterface
     */
    public function getValidatable()
    {
        return $this->validatable;
    }

    /**
     * Returns validation factory.
     *
     * @return \Illuminate\Validation\Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Returns events dispatcher.
     *
     * @return \Illuminate\Events\Dispatcher|null
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * Sets events dispatcher.
     *
     * @param \Illuminate\Events\Dispatcher $dispatcher
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Unset events dispatcher.
     *
     * @return void
     */
    public static function unsetEventDispatcher()
    {
        static::$dispatcher = null;
    }
}