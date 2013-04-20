<?php

use Mockery as m;

class FactoryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }


    public function testFactoryCreation()
    {
        $f = $this->getFactory();

        $this->assertInstanceOf('Krucas\Service\Validator\Factory', $f);
    }


    public function testGetIlluminateFactory()
    {
        $f = $this->getFactory();

        $this->assertInstanceOf('Illuminate\Validation\Factory', $f->getIlluminateValidationFactory());
    }


    public function testCreateValidatorService()
    {
        $f = $this->getFactory();

        $mock = m::mock('Krucas\Service\Validator\Contracts\ValidatableInterface');

        $mock->shouldReceive('getValidationValues')->once()->andReturn(array());
        $mock->shouldReceive('getValidationRules')->once()->andReturn(array());

        $this->assertInstanceOf('Krucas\Service\Validator\Validator', $f->make($mock));
    }


    public function getFactory()
    {
        return new \Krucas\Service\Validator\Factory(
            m::mock('Illuminate\Validation\Factory')
        );
    }
}