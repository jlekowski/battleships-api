<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\Ships;
use Symfony\Component\Validator\Constraint;

class ShipsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTargets()
    {
        $constraint = new Ships();
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $constraint = new Ships();
        $this->assertEquals('AppBundle\Validator\Constraints\ShipsValidator', $constraint->validatedBy());
    }
}
