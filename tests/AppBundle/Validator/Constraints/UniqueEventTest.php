<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\UniqueEvent;

class UniqueEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDefaultOption()
    {
        $constraint = new UniqueEvent();
        $this->assertEquals('uniqueEvents', $constraint->getDefaultOption());
    }

    public function testValidatedBy()
    {
        $constraint = new UniqueEvent();
        $this->assertEquals('unique_event', $constraint->validatedBy());
    }

    public function testUniqueEvents()
    {
        $uniqueEvents = ['unique_event1', 'unique_event2'];
        $constraint = new UniqueEvent($uniqueEvents);
        $this->assertEquals($uniqueEvents, $constraint->uniqueEvents);
    }
}
