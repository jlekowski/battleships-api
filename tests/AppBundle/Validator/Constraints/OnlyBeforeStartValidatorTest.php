<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Validator\Constraints\OnlyBeforeStartValidator;
use Prophecy\Prophecy\ObjectProphecy;

class OnlyBeforeStartValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectProphecy
     */
    protected $entityManager;

    /**
     * @var OnlyBeforeStartValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->entityManager = $this->prophesize('Doctrine\ORM\EntityManagerInterface');
        $this->validator = new OnlyBeforeStartValidator($this->entityManager->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\OnlyBeforeStart"
     */
    public function testValidateThrowsExceptionWhenInvalidConstraintProvided()
    {
        $constraint = $this->prophesize('Symfony\Component\Validator\Constraint');

        $this->validator->validate('test', $constraint->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Entity\Game"
     */
    public function testValidateThrowsExceptionWhenInvalidValueProvided()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\OnlyBeforeStart');

        $this->validator->validate('test', $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage Ships can't be changed - game has already started
     */
    public function testValidateThrowsExceptionWhenShipsAlreadySet()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\OnlyBeforeStart');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $unitOfWork = $this->prophesize('Doctrine\ORM\UnitOfWork');
        $changes = [];

        $this->entityManager->getUnitOfWork()->willReturn($unitOfWork);
        $unitOfWork->getEntityChangeSet($game)->willReturn($changes);
        $game->getPlayerNumber()->willReturn(1);
        $game->getPlayerShips()->willReturn(['A1']);

        $this->validator->validate($game->reveal(), $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage Ships can't be changed - game has already started
     */
    public function testValidateThrowsExceptionWhenShipsToBeSet()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\OnlyBeforeStart');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $unitOfWork = $this->prophesize('Doctrine\ORM\UnitOfWork');
        $changes = ['user1Ships' => [['non-empty array']]];

        $this->entityManager->getUnitOfWork()->willReturn($unitOfWork);
        $unitOfWork->getEntityChangeSet($game)->willReturn($changes);
        $game->getPlayerNumber()->willReturn(1);

        $this->validator->validate($game->reveal(), $constraint->reveal());
    }

    public function testValidateWhenShipsNotSetYet()
    {
        $constraint = $this->prophesize('AppBundle\Validator\Constraints\OnlyBeforeStart');
        $game = $this->prophesize('AppBundle\Entity\Game');
        $unitOfWork = $this->prophesize('Doctrine\ORM\UnitOfWork');
        $changes = [];

        $this->entityManager->getUnitOfWork()->willReturn($unitOfWork);
        $unitOfWork->getEntityChangeSet($game)->willReturn($changes);
        $game->getPlayerNumber()->willReturn(1);
        $game->getPlayerShips()->willReturn([]);

        $this->validator->validate($game->reveal(), $constraint->reveal());
    }
}
