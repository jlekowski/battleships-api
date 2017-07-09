<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\IsAllowedToShoot;
use AppBundle\Validator\Constraints\IsAllowedToShootValidator;
use Symfony\Component\Validator\Constraint;

class IsAllowedToShootTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new IsAllowedToShoot();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new IsAllowedToShoot();
        $this->assertEquals(IsAllowedToShootValidator::class, $constraint->validatedBy());
    }
}
