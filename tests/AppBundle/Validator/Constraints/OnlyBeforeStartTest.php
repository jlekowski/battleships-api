<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\OnlyBeforeStart;
use Symfony\Component\Validator\Constraint;

class OnlyBeforeStartTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTargets()
    {
        $constraint = new OnlyBeforeStart();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new OnlyBeforeStart();
        $this->assertEquals('only_before_start_validator', $constraint->validatedBy());
    }
}
