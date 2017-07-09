<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\OnlyBeforeStart;
use AppBundle\Validator\Constraints\OnlyBeforeStartValidator;
use Symfony\Component\Validator\Constraint;

class OnlyBeforeStartTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new OnlyBeforeStart();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new OnlyBeforeStart();
        $this->assertEquals(OnlyBeforeStartValidator::class, $constraint->validatedBy());
    }
}
