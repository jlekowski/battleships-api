<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\EventValue;
use AppBundle\Validator\Constraints\EventValueValidator;
use Symfony\Component\Validator\Constraint;

class EventValueTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new EventValue();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new EventValue();
        $this->assertEquals(EventValueValidator::class, $constraint->validatedBy());
    }
}
