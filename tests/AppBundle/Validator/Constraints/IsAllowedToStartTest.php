<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\IsAllowedToStart;
use Symfony\Component\Validator\Constraint;

class IsAllowedToStartTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTargets()
    {
        $constraint = new IsAllowedToStart();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new IsAllowedToStart();
        $this->assertEquals('is_allowed_to_start', $constraint->validatedBy());
    }
}
