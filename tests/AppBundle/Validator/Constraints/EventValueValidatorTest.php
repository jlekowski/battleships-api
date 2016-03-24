<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Validator\Constraints\EventValueValidator;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class EventValueValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventValueValidator
     */
    protected $validator;

    /**
     * @var ObjectProphecy
     */
    protected $coordsManager;

    public function setUp()
    {
        $this->coordsManager = $this->prophesize('AppBundle\Battle\CoordsManager');
        $this->validator = new EventValueValidator($this->coordsManager->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\EventValue"
     */
    public function testValidateThrowsExceptionWhenInvalidConstraintProvided()
    {
        $constraint = $this->prophesize('Symfony\Component\Validator\Constraint');

        $this->validator->validate('test', $constraint->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Entity\Event"
     */
    public function testValidateThrowsExceptionWhenInvalidValueProvided()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\EventValue');

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateForAnyEvent()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\EventValue');
        $event = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn('any');
        $event->getValue()->shouldNotBeCalled();
        $this->coordsManager->validateCoords(Argument::any())->shouldNotBeCalled();

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage msg
     * @expectedExceptionCode 1
     */
    public function testValidateThrowsExceptionForIncorrectCoords()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\EventValue');
        $event = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getValue()->willReturn('invalidCoord');
        $this->coordsManager->validateCoords('invalidCoord')->willThrow(new \Exception('msg', 1));

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    public function testValidateForShotEventWithCorrectCoords()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\EventValue');
        $event = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getValue()->willReturn('validCoord');
        $this->coordsManager->validateCoords('validCoord')->shouldBeCalled();

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }
}
