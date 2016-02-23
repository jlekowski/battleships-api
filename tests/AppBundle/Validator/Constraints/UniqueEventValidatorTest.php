<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\UniqueEventValidator;
use Prophecy\Prophecy\ObjectProphecy;

class UniqueEventValidatorTest extends \PHPUnit_Framework_TestCase
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
        $this->eventRepository = $this->prophesize('AppBundle\Entity\EventRepository');
        $this->context = $this->prophesize('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->validator = new UniqueEventValidator($this->eventRepository->reveal());
        $this->validator->initialize($this->context->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\UniqueEvent"
     */
    public function testValidateThrowsExceptionWhenInvalidConstraintProvided()
    {
        $constraint = $this->prophesize('Symfony\Component\Validator\Constraint');

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateInNonEventContext()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\UniqueEvent');

        $this->context->getRoot()->willReturn('test');

        $this->validator->validate('test', $constraint->reveal());
    }

    public function testValidateForNonUniqueValue()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\UniqueEvent');
        $event = $this->prophesize('AppBundle\Entity\Event');

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
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\UniqueEvent');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');

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
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\UniqueEvent');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $collection = $this->prophesize('Doctrine\Common\Collections\Collection');

        $constraint->uniqueEvents = ['unique event1', 'unique event2'];
        $this->context->getRoot()->willReturn($event);
        $event->getGame()->willReturn($game);
        $game->getPlayerNumber()->willReturn(1);
        $this->eventRepository->findForGameByTypeAndPlayer($game, 'non-unique event', 1)->willReturn($collection);
        $collection->isEmpty()->willReturn(true);

        $this->validator->validate('non-unique event', $constraint->reveal());
    }
}
