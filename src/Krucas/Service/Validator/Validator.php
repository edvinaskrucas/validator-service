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
     * Allows further actions or not
     *
     * @var bool
     */
    protected $further = true;

    /**
     * Child validator services.
     *
     * @var array
     */
    protected $childValidators = array();

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

        $this->attributes   = $validatable->getValidationAttributes();
        $this->rules        = $validatable->getValidationRules();

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

        $passed = true;

        foreach($this->childValidators as $childValidator)
        {
            if(!$childValidator->passes())
            {
                $passed = false;
                $this->mergeErrors($childValidator->getErrors());
            }
        }

        $validator = $this->factory->make($this->getAttributes(), $this->getRules());

        if(!$validator->passes())
        {
            $passed = false;
        }

        if(!$passed)
        {
            $this->mergeErrors($validator->errors());
        }
        else
        {
            $this->fireEvent('validated', false);
        }

        return $passed;
    }

    /**
     * Merges messages.
     *
     * @param \Illuminate\Support\MessageBag $bag
     * @return void
     */
    protected function mergeErrors(MessageBag $bag)
    {
        if(is_null($this->errors)) $this->errors = new MessageBag();

        $this->errors->merge($bag->getMessages());
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
     * Sets attributes values with rules.
     *
     * @param array $attributes
     * @return \Krucas\Service\Validator\Validator
     */
    public function setValuesWithRules(array $attributes = array())
    {
        foreach($attributes as $attribute => $values)
        {
            $this->attributes[$attribute]   = $values['value'];
            $this->rules[$attribute]        = $values['rules'];
        }

        return $this;
    }

    /**
     * Returns all attributes values with rules.
     *
     * @return array
     */
    public function getValuesWithRules()
    {
        $arr = array();

        foreach($this->attributes as $attr => $value)
        {
            $arr[$attr] = array('value' => $value, 'rules' => $this->getAttributeRules($attr));
        }

        return $arr;
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
     * @return string|null
     */
    public function getAttributeRules($attribute)
    {
        return isset($this->rules[$attribute]) ? $this->rules[$attribute] : null;
    }

    /**
     * Returns value of a given attribute.
     *
     * @param $attribute
     * @return mixed|null
     */
    public function getAttributeValue($attribute)
    {
        return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
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
     * Determines if validation actions after init will be executed.
     *
     * @return bool
     */
    public function isFurther()
    {
        return $this->further;
    }

    /**
     * Returns array of child validators.
     *
     * @return array
     */
    public function getChildValidators()
    {
        return $this->childValidators;
    }

    /**
     * Add a child validator.
     *
     * @param \Krucas\Service\Validator\Validator $validator
     * @param null $name
     * @return \Krucas\Service\Validator\Validator
     */
    public function addChildValidator(Validator $validator, $name = null)
    {
        if(is_null($name))
        {
            $this->childValidators[] = $validator;
        }
        else
        {
            $this->childValidators[$name] = $validator;
        }

        return $this;
    }

    /**
     * Returns child validator based on its key / name.
     *
     * @param $key
     * @return \Krucas\Service\Validator\Validator
     */
    public function getChildValidator($key)
    {
        return $this->childValidators[$key];
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
        if(isset($this->attributes[$offset]))
        {
            return true;
        }
        else
        {
            foreach($this->childValidators as $child)
            {
                if(isset($child[$offset]))
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns attribute value of a given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if(!is_null($this->getAttributeValue($offset)))
        {
            return $this->getAttributeValue($offset);
        }

        foreach($this->childValidators as $child)
        {
            if(!is_null($child[$offset]))
            {
                return $child[$offset];
            }
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
        $this->setAttributeValue($offset, $value);
    }

    /**
     * Unset attribute for a given offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);

        foreach($this->childValidators as $child)
        {
            unset($child[$offset]);
        }
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $arr = array();

        foreach($this->getAttributes() as $attribute => $value)
        {
            $arr[$attribute] = array('value' => $value, 'rules' => $this->getAttributeRules($attribute));
        }

        return $arr;
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