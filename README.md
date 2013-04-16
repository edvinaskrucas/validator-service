# Validator Service for Laravel 4

---

Simple, yet powered with features validator service to validate your data.

---

## Installation

As it is using composer packaging system you need just to add this ```"edvinaskrucas/validator-service": "*""``` to your composer.json file and update your project.

### Laravel service provider

When using it with laravel4 you may want to add these lines to your config file:

ServiceProvider array
```php
'Krucas\Service\Validator\ValidatorServiceProvider'
```

and Alias array
```php
'ValidatorService' => 'Krucas\Service\Validator\Facades\ValidatorService'
```

Now you can use power of facades.

## Events

Validator service uses events to let other components to know that validator is doing some checks.

Events before actual validation is started:
* service.validator.validating
* service.validator.validating: Vendor\Package\Class

Events after validation:
* service.validator.validated
* service.validator.validated: Vendor\Package\Class

Lets overview them quickly.

### service.validator.validating

This event is fired first, and if some listener returned ```false``` then it will cancel validating and return ```false```

### service.validator.validating: Vendor\Package\Class

Event is almost the same is previous one, expect this lets you to listen to a certain class to be validated.
Where ```Vendor\Package\Class``` validated class name will be placed.
If some listeners returned ```false```, then validation method will be canceled.

### service.validator.validated

Event is fired just when validation returned ```true```, this event wont stop any further actions.

### service.validator.validated Vendor\Package\Class

Almost same as above, but with a class name.

---

All events are passing a ```Krucas\Service\Validator\Validator``` object instance to manipulate it.

## Usage

### Basic usage

You can use it to validate your models, forms and other stuff, you just need to implement ```ValidatableInterface``` and you are ready.

Eloquent sample model:
```php
class Page extends Eloquent implements Krucas\Service\Validator\Contracts\ValidatableInterface
{
    public function getValidationRules()
    {
        return array(
            'title'     => 'required|max:255',
            'content'   => 'required'
        );
    }

    public function getValidationAttributes()
    {
        return $this->attributes;
    }
}
```

Now you are ready to validate it.
```php
$page = new Page();

$validatorService = ValidatorService::make($page);

if($validatorService->passes())
{
    return 'OK';
}
else
{
    $errors = $validatorService->getErrors();
}
```

This example shows how easily you can set up your validation.

### Advanced usage with event listeners

This example will show more advanced usage (I used this in my case).

We have a package named Routing, basically what it does is just stores some URL's to a database and resolves objects from a polymorphic relations.

Lets define our interface for a routable models.

```php
interface RoutableInterface
{
    public function getUri();
}
```

Now we need to handle all routable models, add additional checks when validating our data, we can do this very easy when listening for some events.
```php
Event::listen('service.validator.validating', function(Validator $validatorService)
{
    // Check if our validatable object implements RoutableInterface
    // If it is, then add some extra rules and values for a validator
    if(in_array('RoutableInterface', class_implements($validatorService->getValidatable())))
    {
        $validatorService->setAttributeRules('uri', 'required|max:255|unique:uri,uri');
        $validatorService->setAttributeValue('uri', Input::get('uri'));
    }
});
```

Thats it, this will inject some extra rules and values for a every Routable model instance when it is validating. After success validation you can insert some records to your db.