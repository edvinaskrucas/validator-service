<?php

use Mockery as m;

class ValidationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }


    public function testValidatorServiceCreation()
    {
        $s = $this->getValidatorService();

        $this->assertInstanceOf('Krucas\Service\Validator\Validator', $s);
    }


    public function testFactoryInstance()
    {
        $s = $this->getValidatorService();

        $this->assertInstanceOf('Illuminate\Validation\Factory', $s->getFactory());
    }


    public function testValidatableInstance()
    {
        $s = $this->getValidatorService();

        $this->assertInstanceOf('Krucas\Service\Validator\Contracts\ValidatableInterface', $s->getValidatable());
    }


    public function testSetRulesAndGetRules()
    {
        $s = $this->getValidatorService();

        $this->assertInstanceOf(get_class($s), $s->setRules(array('test' => 'test')));
        $this->assertEquals(array('test' => 'test'), $s->getRules());
    }


    public function testSetAttributesAndGetAttributes()
    {
        $s = $this->getValidatorService();

        $this->assertInstanceOf(get_class($s), $s->setAttributes(array('test' => 'test')));
        $this->assertEquals(array('test' => 'test'), $s->getAttributes());
    }


    public function testSetAttributeRulesAndRetrieveIt()
    {
        $s = $this->getValidatorService();

        $this->assertInstanceOf(get_class($s), $s->setAttributeRules('test', 'test'));
        $this->assertEquals('test' , $s->getAttributeRules('test'));
    }


    public function testGetAttributeRulesAndValueWhenItIsNotSet()
    {
        $s = $this->getValidatorService();

        $this->assertNull($s->getAttributeRules('test'));
        $this->assertNull($s->getAttributeValue('test'));
    }


    public function testSetAttributeValueAndRetrieveIt()
    {
        $s = $this->getValidatorService();

        $this->assertInstanceOf(get_class($s), $s->setAttributeValue('test', 'value'));
        $this->assertEquals('value' , $s->getAttributeValue('test'));
    }


    public function testRemoveAttribute()
    {
        $s = $this->getValidatorService();

        $s->setAttributes(array('foo' => 'bar', 'bar' => 'foo'));
        $s->setRules(array('foo' => 'test', 'bar' => 'test'));

        $this->assertEquals(array('foo' => 'bar', 'bar' => 'foo'), $s->getAttributes());
        $this->assertEquals(array('foo' => 'test', 'bar' => 'test'), $s->getRules());

        $s->removeAttribute('foo');

        $this->assertEquals(array('bar' => 'foo'), $s->getAttributes());
        $this->assertEquals(array('bar' => 'test'), $s->getRules());
    }


    public function testEventDispatcherSetUp()
    {
        $s = $this->getValidatorService();

        $this->assertNull(\Krucas\Service\Validator\Validator::getEventDispatcher());

        $this->setEventDispatcher();

        $this->assertInstanceOf('Illuminate\Events\Dispatcher', \Krucas\Service\Validator\Validator::getEventDispatcher());

        \Krucas\Service\Validator\Validator::unsetEventDispatcher();

        $this->assertNull(\Krucas\Service\Validator\Validator::getEventDispatcher());
    }


    public function testValidationPasses()
    {
        $s = $this->getValidatorService();

        $s->getFactory()->shouldReceive('make')->once()->andReturn($validator = m::mock('Illuminate\Validation\Validator'));
        $validator->shouldReceive('passes')->once()->andReturn(true);
        $s->setAttributeRules('test', 'test');

        $this->assertTrue($s->passes());
        $this->assertNull($s->getErrors());
        $this->assertNull($s->getMessageBag());
    }


    public function testValidationFails()
    {
        $s = $this->getValidatorService();

        $s->getFactory()->shouldReceive('make')->once()->andReturn($validator = m::mock('Illuminate\Validation\Validator'));
        $validator->shouldReceive('passes')->once()->andReturn(false);
        $validator->shouldReceive('errors')->once()->andReturn($bag = m::mock('Illuminate\Support\MessageBag'));
        $bag->shouldReceive('getMessages')->once()->andReturn(array('test' => 'test'));
        $s->setAttributeRules('test', 'test');

        $this->assertFalse($s->passes());
        $this->assertInstanceOf('Illuminate\Support\MessageBag', $s->getErrors());
        $this->assertInstanceOf('Illuminate\Support\MessageBag', $s->getMessageBag());
    }


    public function testValidationPassesWithEvents()
    {
        $s = $this->getValidatorService();
        $this->setEventDispatcher();

        $s->getFactory()->shouldReceive('make')->once()->andReturn($validator = m::mock('Illuminate\Validation\Validator'));
        $validator->shouldReceive('passes')->once()->andReturn(true);
        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->with('service.validator.validating', $s)
            ->andReturn(true);

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->with('service.validator.validating: '.get_class($s->getValidatable()), $s)
            ->andReturn(true);

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('fire')
            ->once()
            ->with('service.validator.validated', $s)
            ->andReturn(true);

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('fire')
            ->once()
            ->with('service.validator.validated: '.get_class($s->getValidatable()), $s)
            ->andReturn(true);

        $s->setAttributeRules('test', 'test');

        $this->assertTrue($s->passes());
        $this->assertNull($s->getErrors());
        $this->assertNull($s->getMessageBag());
    }


    public function testValidationFailsWithEventsFailsOnGlobalEvent()
    {
        $this->setEventDispatcher();

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->times(4)
            ->andReturn(true);

        $s = $this->getValidatorService();
        $s->setAttributeRules('test', 'test');

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->with('service.validator.validating', $s)
            ->andReturn(false);

        $this->assertFalse($s->passes());
        $this->assertNull($s->getErrors());
        $this->assertNull($s->getMessageBag());
    }


    public function testValidationFailsWithEventsFailsOnEvent()
    {
        $this->setEventDispatcher();

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->times(4)
            ->andReturn(true);

        $s = $this->getValidatorService();
        $s->setAttributeRules('test', 'test');

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->with('service.validator.validating', $s)
            ->andReturn(true);

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->with('service.validator.validating: '.get_class($s->getValidatable()), $s)
            ->andReturn(false);

        $this->assertFalse($s->passes());
        $this->assertNull($s->getErrors());
        $this->assertNull($s->getMessageBag());
    }


    public function testArrayAccessForAttributes()
    {
        $s = $this->getValidatorService();

        $s['test'] = 'test';

        $this->assertEquals('test', $s['test']);
        $this->assertFalse(isset($s['a']));

        unset($s['test']);

        $this->assertFalse(isset($s['test']));
    }


    public function testFailOnGlobalCreatedEvent()
    {
        $this->setEventDispatcher();

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->times(3)
            ->andReturn(true);

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->andReturn(false);

        $this->assertFalse($this->getValidatorService()->isFurther());
    }


    public function testFailOnCreatedEvent()
    {
        $this->setEventDispatcher();

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->times(3)
            ->andReturn(true);

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->andReturn(false);

        $this->assertFalse($this->getValidatorService()->isFurther());
    }


    public function testAddChildValidator()
    {
        $s = $this->getValidatorService();

        $s
            ->addChildValidator($this->getValidatorService())
            ->addChildValidator($this->getValidatorService());

        $this->assertCount(2, $s->getChildValidators());
        $this->assertInstanceOf('Krucas\Service\Validator\Validator', $s->getChildValidators()[0]);
        $this->assertInstanceOf('Krucas\Service\Validator\Validator', $s->getChildValidators()[1]);
    }


    public function testValidateWithChildValidator()
    {
        $s = $this->getValidatorService();
        $s->addChildValidator($child = $this->getValidatorService());

        $s->getFactory()->shouldReceive('make')->once()->andReturn($validator = m::mock('Illuminate\Validation\Validator'));
        $validator->shouldReceive('passes')->once()->andReturn(true);
        $validator->shouldReceive('errors')->once()->andReturn($bag = m::mock('Illuminate\Support\MessageBag'));
        $bag->shouldReceive('getMessages')->once()->andReturn(array('parent' => 'test'));

        $child->getFactory()->shouldReceive('make')->once()->andReturn($childValidator = m::mock('Illuminate\Validation\Validator'));
        $childValidator->shouldReceive('passes')->once()->andReturn(false);
        $childValidator->shouldReceive('errors')->once()->andReturn($childBag = m::mock('Illuminate\Support\MessageBag'));
        $childBag->shouldReceive('getMessages')->once()->andReturn(array('child' => 'test'));

        $this->assertFalse($s->passes());
        $this->assertCount(2, $s->getErrors());
        $this->assertEquals('test', $s->getErrors()->first('parent'));
        $this->assertEquals('test', $s->getErrors()->first('child'));
    }


    public function setEventDispatcher()
    {
        \Krucas\Service\Validator\Validator::setEventDispatcher(m::mock('Illuminate\Events\Dispatcher'));
    }


    public function getValidatorService()
    {
        $mock = m::mock('Krucas\Service\Validator\Contracts\ValidatableInterface');

        $mock->shouldReceive('getValidationAttributes')->once()->andReturn(array());
        $mock->shouldReceive('getValidationRules')->once()->andReturn(array());

        return new \Krucas\Service\Validator\Validator(
            m::mock('Illuminate\Validation\Factory'),
            $mock
        );
    }
}