<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Validator\Constraints\IsAllowedToStartValidator;

class IsAllowedToStartValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IsAllowedToStartValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->validator = new IsAllowedToStartValidator();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\IsAllowedToStart"
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
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToStart');

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateForNonStartEvent()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToStart');
        $event = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_NEW_GAME);
        $event->getGame()->shouldNotBeCalled();

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage You must set ships first
     */
    public function testValidateThrowsExceptionWhenShipsNotSet()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToStart');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');

        $event->getType()->willReturn(Event::TYPE_START_GAME);
        $event->getGame()->willReturn($game);
        $game->getPlayerShips()->willReturn([]);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    public function testValidateAfterShipsSet()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToStart');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');

        $event->getType()->willReturn(Event::TYPE_START_GAME);
        $event->getGame()->willReturn($game);
        $game->getPlayerShips()->willReturn(['A1']);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }
}
