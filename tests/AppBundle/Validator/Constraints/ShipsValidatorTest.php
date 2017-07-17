<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Battle\CoordsManager;
use AppBundle\Validator\Constraints\Ships;
use AppBundle\Validator\Constraints\ShipsValidator;
use Symfony\Component\Validator\Constraint;

class ShipsValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShipsValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->validator = new ShipsValidator(new CoordsManager());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\Ships"
     */
    public function testValidateThrowsExceptionWhenInvalidConstraintProvided()
    {
        $constraint = $this->prophesize(Constraint::class);

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateForNoShips()
    {
        $constraint = $this->prophesize(Ships::class);

        $this->validator->validate([], $constraint->reveal());
    }

    public function testValidateCorrectShips()
    {
        $constraint = $this->prophesize(Ships::class);

        $ships = ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'];

        $this->validator->validate($ships, $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidShipsException
     * @expectedExceptionMessage Number of ships' masts is incorrect: 19 (expected: 20)
     */
    public function testValidateThrowsExceptionWhenNotEnoughMasts()
    {
        $constraint = $this->prophesize(Ships::class);

        $ships = ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10'];

        $this->validator->validate($ships, $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidShipsException
     * @expectedExceptionMessage Number of ships' masts is incorrect: 21 (expected: 20)
     */
    public function testValidateThrowsExceptionWhenTooManyMasts()
    {
        $constraint = $this->prophesize(Ships::class);

        $ships = ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10','A10'];

        $this->validator->validate($ships, $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidShipsException
     * @expectedExceptionMessage Ships's corners can't touch each other
     */
    public function testValidateThrowsExceptionWhenEdgeConnection()
    {
        $constraint = $this->prophesize(Ships::class);

        $ships = ['B1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'];

        $this->validator->validate($ships, $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidShipsException
     * @expectedExceptionMessage Ships can't have more than 4 masts
     */
    public function testValidateThrowsExceptionWhenShipTooLong()
    {
        $constraint = $this->prophesize(Ships::class);

        $ships = ['F4','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'];

        $this->validator->validate($ships, $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidShipsException
     * @expectedExceptionMessage Number of ships' types is incorrect
     */
    public function testValidateThrowsExceptionWhenShipsTypesIncorrect()
    {
        $constraint = $this->prophesize(Ships::class);

        $ships = ['B2','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'];

        $this->validator->validate($ships, $constraint->reveal());
    }
}
