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

## Usage

### Basic Eloquent usage

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

### Events

will come soon... :)