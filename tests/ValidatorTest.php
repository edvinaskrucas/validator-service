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
    }


    public function testValidationFails()
    {
        $s = $this->getValidatorService();

        $s->getFactory()->shouldReceive('make')->once()->andReturn($validator = m::mock('Illuminate\Validation\Validator'));
        $validator->shouldReceive('passes')->once()->andReturn(false);
        $validator->shouldReceive('errors')->once()->andReturn(m::mock('Illuminate\Support\MessageBag'));
        $s->setAttributeRules('test', 'test');

        $this->assertFalse($s->passes());
        $this->assertInstanceOf('Illuminate\Support\MessageBag', $s->getErrors());
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
    }


    public function testValidationFailsWithEventsFailsOnGlobalEvent()
    {
        $s = $this->getValidatorService();
        $this->setEventDispatcher();

        $s->setAttributeRules('test', 'test');

        \Krucas\Service\Validator\Validator::getEventDispatcher()
            ->shouldReceive('until')
            ->once()
            ->with('service.validator.validating', $s)
            ->andReturn(false);

        $this->assertFalse($s->passes());
        $this->assertNull($s->getErrors());
    }


    public function testValidationFailsWithEventsFailsOnEvent()
    {
        $s = $this->getValidatorService();
        $this->setEventDispatcher();

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