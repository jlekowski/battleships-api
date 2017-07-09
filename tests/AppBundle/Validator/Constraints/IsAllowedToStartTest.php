<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\IsAllowedToStart;
use AppBundle\Validator\Constraints\IsAllowedToStartValidator;
use Symfony\Component\Validator\Constraint;

class IsAllowedToStartTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new IsAllowedToStart();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new IsAllowedToStart();
        $this->assertEquals(IsAllowedToStartValidator::class, $constraint->validatedBy());
    }
}
