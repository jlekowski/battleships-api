<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use AppBundle\Validator\Constraints\IsAllowedToStart;
use AppBundle\Validator\Constraints\IsAllowedToStartValidator;
use Symfony\Component\Validator\Constraint;

class IsAllowedToStartValidatorTest extends \PHPUnit\Framework\TestCase
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
        $constraint = $this->prophesize(Constraint::class);

        $this->validator->validate('test', $constraint->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Entity\Event"
     */
    public function testValidateThrowsExceptionWhenInvalidValueProvided()
    {
        $constraint = $this->prophesize(IsAllowedToStart::class);

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateForNonStartEvent()
    {
        $constraint = $this->prophesize(IsAllowedToStart::class);
        $event = $this->prophesize(Event::class);

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
        $constraint = $this->prophesize(IsAllowedToStart::class);
        $event = $this->prophesize(Event::class);
        $game = $this->prophesize(Game::class);

        $event->getType()->willReturn(Event::TYPE_START_GAME);
        $event->getGame()->willReturn($game);
        $game->getPlayerShips()->willReturn([]);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    public function testValidateAfterShipsSet()
    {
        $constraint = $this->prophesize(IsAllowedToStart::class);
        $event = $this->prophesize(Event::class);
        $game = $this->prophesize(Game::class);

        $event->getType()->willReturn(Event::TYPE_START_GAME);
        $event->getGame()->willReturn($game);
        $game->getPlayerShips()->willReturn(['A1']);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }
}
