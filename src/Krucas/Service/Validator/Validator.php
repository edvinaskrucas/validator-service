<?php namespace Krucas\Service\Validator;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\MessageProviderInterface;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Factory as IlluminateValidationFactory;
use Krucas\Service\Validator\Contracts\ValidatableInterface;
use ArrayAccess;

class Validator implements ArrayAccess, MessageProviderInterface, ArrayableInterface
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
     * Attribute names with value and rules.
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
     * Allows further actions or not
     *
     * @var bool
     */
    protected $further = true;

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
        if($this->fireEvent('creating') === false) $this->further = false;

        $this->factory      = $factory;
        $this->validatable  = $validatable;

        $this->setValues($validatable->getValidationValues());
        $this->setRules($validatable->getValidationRules());

        if($this->fireEvent('created') === false) $this->further = false;
    }

    /**
     * Validates object and fires events.
     *
     * @return bool
     */
    protected function validate()
    {
        if($this->further === false) return false;

        if($this->fireEvent('validating') === false) return false;

        $validator = $this->factory->make($this->getValues(), $this->getRules());

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
        if(is_null(static::$dispatcher)) return true;

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
     * Returns validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        $arr = array();

        foreach($this->attributes as $attribute => $values)
        {
            $arr[$attribute] = $this->getAttributeRules($attribute);
        }

        return $arr;
    }

    /**
     * Sets validation rules.
     *
     * @param array $rules
     * @return \Krucas\Service\Validator\Validator
     */
    public function setRules(array $rules = array())
    {
        foreach($rules as $attribute => $rule)
        {
            $this->setAttributeRules($attribute, $rule);
        }

        return $this;
    }

    /**
     * Sets attributes values.
     *
     * @param array $values
     * @return \Krucas\Service\Validator\Validator
     */
    public function setValues(array $values = array())
    {
        foreach($values as $attribute => $value)
        {
            $this->setAttributeValue($attribute, $value);
        }

        return $this;
    }

    /**
     * Returns attributes values.
     *
     * @return array
     */
    public function getValues()
    {
        $arr = array();

        foreach($this->attributes as $attribute => $values)
        {
            $arr[$attribute] = $this->getAttributeValue($attribute);
        }

        return $arr;
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
     * Sets attributes values with rules.
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
     * Adds new attribtues to attributes array.
     *
     * @param array $attributes
     * @return \Krucas\Service\Validator\Validator
     */
    public function addAttributes(array $attributes = array())
    {
        foreach($attributes as $attribute => $values)
        {
            $this->addAttribute($attribute, $values['value'], $values['rules']);
        }

        return $this;
    }

    /**
     * Adds single attribute value and rules.
     *
     * @param $attribute
     * @param $value
     * @param $rules
     * @return \Krucas\Service\Validator\Validator
     */
    public function addAttribute($attribute, $value, $rules)
    {
        $this->setAttributeValue($attribute, $value);
        $this->setAttributeRules($attribute, $rules);

        return $this;
    }

    /**
     * Sets rules for a given attribute.
     *
     * @param string $attribute
     * @param string $rules
     * @return \Krucas\Service\Validator\Validator
     */
    public function setAttributeRules($attribute, $rules)
    {
        $this->arraySet(ends_with($attribute, '.rules') ? $attribute : $attribute.'.rules', $rules);

        return $this;
    }

    /**
     * Returns rules for a given attribute.
     *
     * @param $attribute
     * @return string|null
     */
    public function getAttributeRules($attribute)
    {
        return array_get($this->toArray(), ends_with($attribute, '.rules') ? $attribute : $attribute.'.rules', null);
    }

    /**
     * Returns value of a given attribute.
     *
     * @param $attribute
     * @return mixed|null
     */
    public function getAttributeValue($attribute)
    {
        return array_get($this->toArray(), ends_with($attribute, '.value') ? $attribute : $attribute.'.value', null);
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
        $this->arraySet(ends_with($attribute, '.value') ? $attribute : $attribute.'.value', $value);

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
        $keys = explode('.', $attribute);

        $count = 1;

        if(last($keys) == 'value' || last($keys) == 'rules') $count = 2;

        if(count($keys) == $count)
        {
            array_forget($this->attributes, $attribute);
        }

        return $this;
    }

    /**
     * Determines if validation actions after init will be executed.
     *
     * @return bool
     */
    public function isFurther()
    {
        return $this->further;
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
     * Get the messages for the instance.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this->getErrors();
    }

    /**
     * Determines if a attribute with a given key exists.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_get($this->toArray(), $offset) ? true : false;
    }

    /**
     * Returns attribute value of a given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $keys = explode('.', $offset);

        if(last($keys) == 'value')
        {
            return $this->getAttributeValue($offset);
        }
        elseif(last($keys) == 'rules')
        {
            return $this->getAttributeRules($offset);
        }
        else
        {
            return $this->getAttributeValue($offset);
        }

        return null;
    }

    /**
     * Sets attribute value for a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if(is_array($value))
        {
            $this->addAttributes(array($offset => $value));
        }
        else
        {
            $keys = explode('.', $offset);

            if(last($keys) == 'value')
            {
                $this->setAttributeValue($offset, $value);
            }
            elseif(last($keys) == 'rules')
            {
                $this->setAttributeRules($offset, $value);
            }
            else
            {
                $this->setAttributeValue($offset, $value);
            }
        }
    }

    /**
     * Unset attribute for a given offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->removeAttribute($offset);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Set value using dot syntax to proper array.
     *
     * @param $offset
     * @param $value
     */
    protected function arraySet($offset, $value)
    {
        $keys = explode('.', $offset);

        if(count($keys) == 2)
        {
            array_set($this->attributes, $offset, $value);
        }
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