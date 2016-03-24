<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\EventValue;
use Symfony\Component\Validator\Constraint;

class EventValueTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTargets()
    {
        $constraint = new EventValue();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new EventValue();
        $this->assertEquals('event_value', $constraint->validatedBy());
    }
}
