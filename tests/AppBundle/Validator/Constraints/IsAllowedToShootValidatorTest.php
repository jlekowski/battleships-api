<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Validator\Constraints\IsAllowedToShootValidator;
use Prophecy\Prophecy\ObjectProphecy;

class IsAllowedToShootValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IsAllowedToShootValidator
     */
    protected $validator;

    /**
     * @var ObjectProphecy
     */
    protected $eventRepository;

    public function setUp()
    {
        $this->eventRepository = $this->prophesize('AppBundle\Entity\EventRepository');
        $this->validator = new IsAllowedToShootValidator($this->eventRepository->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\IsAllowedToShoot"
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
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateForNonShotEvent()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_START_GAME);
        $event->getGame()->shouldNotBeCalled();

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage Other player has not started yet
     */
    public function testValidateThrowsExceptionWhenOtherPlayerHasNotStarted()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);

        $game->getOtherNumber()->willReturn(2);
        $game->getPlayerNumber()->shouldNotBeCalled();
        $collection->isEmpty()->willReturn(true);
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, 2)->willReturn($collection);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage It's other player's turn
     */
    public function testValidateThrowsExceptionWhenOtherPlayersTurnBecauseNoShots()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $lastShotEvent = false;

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);

        $game->getOtherNumber()->willReturn(1);
        $game->getPlayerNumber()->willReturn(2);
        $collection->isEmpty()->willReturn(false);
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, 1)->willReturn($collection);
        $this->eventRepository->findLastForGameByType($game, Event::TYPE_SHOT)->willReturn($lastShotEvent);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage It's other player's turn
     */
    public function testValidateThrowsExceptionWhenOtherPlayersTurnBecauseLastPlayerMiss()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $lastShotEvent = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);

        $game->getOtherNumber()->willReturn(2);
        $game->getPlayerNumber()->willReturn(1);
        $collection->isEmpty()->willReturn(false);
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, 2)->willReturn($collection);
        $this->eventRepository->findLastForGameByType($game, Event::TYPE_SHOT)->willReturn($lastShotEvent);
        $lastShotEvent->getPlayer()->willReturn(1);
        $lastShotEvent->getValue()->willReturn('A1|miss');
        $game->getOtherShips()->willReturn(['A2']);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage It's other player's turn
     */
    public function testValidateThrowsExceptionWhenOtherPlayersTurnBecauseLastOtherHit()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $lastShotEvent = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);

        $game->getOtherNumber()->willReturn(2);
        $game->getPlayerNumber()->willReturn(1);
        $collection->isEmpty()->willReturn(false);
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, 2)->willReturn($collection);
        $this->eventRepository->findLastForGameByType($game, Event::TYPE_SHOT)->willReturn($lastShotEvent);
        $lastShotEvent->getPlayer()->willReturn(2);
        $lastShotEvent->getValue()->willReturn('A1|hit');
        $game->getPlayerShips()->willReturn(['A1']);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    public function testValidatePlayerTurnBecauseLastPlayerHit()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $lastShotEvent = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);

        $game->getOtherNumber()->willReturn(2);
        $game->getPlayerNumber()->willReturn(1);
        $collection->isEmpty()->willReturn(false);
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, 2)->willReturn($collection);
        $this->eventRepository->findLastForGameByType($game, Event::TYPE_SHOT)->willReturn($lastShotEvent);
        $lastShotEvent->getPlayer()->willReturn(1);
        $lastShotEvent->getValue()->willReturn('A1|hit');
        $game->getOtherShips()->willReturn(['A1']);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    public function testValidatePlayerTurnBecauseLastOtherMiss()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $lastShotEvent = $this->prophesize('AppBundle\Entity\Event');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);

        $game->getOtherNumber()->willReturn(2);
        $game->getPlayerNumber()->willReturn(1);
        $collection->isEmpty()->willReturn(false);
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, 2)->willReturn($collection);
        $this->eventRepository->findLastForGameByType($game, Event::TYPE_SHOT)->willReturn($lastShotEvent);
        $lastShotEvent->getPlayer()->willReturn(2);
        $lastShotEvent->getValue()->willReturn('A1|miss');
        $game->getPlayerShips()->willReturn(['A2']);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }

    public function testValidatePlayerTurnBecauseFirstShot()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\IsAllowedToShoot');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');
        $lastShotEvent = false;

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);

        $game->getOtherNumber()->willReturn(2);
        $game->getPlayerNumber()->willReturn(1);
        $collection->isEmpty()->willReturn(false);
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, 2)->willReturn($collection);
        $this->eventRepository->findLastForGameByType($game, Event::TYPE_SHOT)->willReturn($lastShotEvent);

        $this->validator->validate($event->reveal(), $constraint->reveal());
    }
}