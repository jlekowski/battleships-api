<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use AppBundle\Repository\EventRepository;
use AppBundle\Validator\Constraints\UniqueEvent;
use AppBundle\Validator\Constraints\UniqueEventValidator;
use Doctrine\Common\Collections\Collection;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UniqueEventValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UniqueEventValidator
     */
    protected $validator;

    /**
     * @var ObjectProphecy
     */
    protected $eventRepository;

    /**
     * @var ObjectProphecy
     */
    protected $context;

    public function setUp()
    {
        $this->eventRepository = $this->prophesize(EventRepository::class);
        $this->context = $this->prophesize(ExecutionContextInterface::class);

        $this->validator = new UniqueEventValidator($this->eventRepository->reveal());
        $this->validator->initialize($this->context->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\UniqueEvent"
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
    public function testValidateThrowsExceptionInNonEventContext()
    {
        $constraint = $this->prophesize(UniqueEvent::class);

        $this->context->getRoot()->willReturn('test');

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateForNonUniqueValue()
    {
        $constraint = $this->prophesize(UniqueEvent::class);
        $event = $this->prophesize(Event::class);

        $constraint->uniqueEvents = ['unique event'];
        $this->context->getRoot()->willReturn($event);

        $this->validator->validate('non-unique event', $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\DuplicatedEventTypeException
     * @expectedExceptionMessage Event `unique event2` has already been created
     */
    public function testValidateThrowsExceptionWhenUniqueEventAlreadyExists()
    {
        $constraint = $this->prophesize(UniqueEvent::class);
        $event = $this->prophesize(Event::class);
        $game = $this->prophesize(Game::class);
        $collection = $this->prophesize(Collection::class);

        $constraint->uniqueEvents = ['unique event1', 'unique event2'];
        $this->context->getRoot()->willReturn($event);
        $event->getGame()->willReturn($game);
        $game->getPlayerNumber()->willReturn(1);
        $this->eventRepository->findForGameByTypeAndPlayer($game, 'unique event2', 1)->willReturn($collection);
        $collection->isEmpty()->willReturn(false);

        $this->validator->validate('unique event2', $constraint->reveal());
    }

    public function testValidateWhenEventNotCreatedYet()
    {
        $constraint = $this->prophesize(UniqueEvent::class);
        $event = $this->prophesize(Event::class);
        $game = $this->prophesize(Game::class);
        $collection = $this->prophesize(Collection::class);

        $constraint->uniqueEvents = ['unique event1', 'unique event2'];
        $this->context->getRoot()->willReturn($event);
        $event->getGame()->willReturn($game);
        $game->getPlayerNumber()->willReturn(1);
        $this->eventRepository->findForGameByTypeAndPlayer($game, 'non-unique event', 1)->willReturn($collection);
        $collection->isEmpty()->willReturn(true);

        $this->validator->validate('non-unique event', $constraint->reveal());
    }
}
