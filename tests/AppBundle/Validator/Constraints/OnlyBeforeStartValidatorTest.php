<?php

namespace Tests\AppBundle\Validator\Constraints;

use AppBundle\Entity\Game;
use AppBundle\Validator\Constraints\OnlyBeforeStart;
use AppBundle\Validator\Constraints\OnlyBeforeStartValidator;
use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Validator\Constraint;

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
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->validator = new OnlyBeforeStartValidator($this->entityManager->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Validator\Constraints\OnlyBeforeStart"
     */
    public function testValidateThrowsExceptionWhenInvalidConstraintProvided()
    {
        $constraint = $this->prophesize(Constraint::class);

        $this->validator->validate('test', $constraint->reveal());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "AppBundle\Entity\Game"
     */
    public function testValidateThrowsExceptionWhenInvalidValueProvided()
    {
        $constraint = $this->prophesize(OnlyBeforeStart::class);

        $this->validator->validate('test', $constraint->reveal());
    }

    /**
     * @expectedException \AppBundle\Exception\GameFlowException
     * @expectedExceptionMessage Ships can't be changed - game has already started
     */
    public function testValidateThrowsExceptionWhenShipsAlreadySet()
    {
        $constraint = $this->prophesize(OnlyBeforeStart::class);
        $game = $this->prophesize(Game::class);
        $unitOfWork = $this->getUnitOfWork([]);

        $this->entityManager->getUnitOfWork()->willReturn($unitOfWork);
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
        $constraint = $this->prophesize(OnlyBeforeStart::class);
        $game = $this->prophesize(Game::class);
        $unitOfWork = $this->getUnitOfWork(['user1Ships' => [['non-empty array']]]);

        $this->entityManager->getUnitOfWork()->willReturn($unitOfWork);
        $game->getPlayerNumber()->willReturn(1);

        $this->validator->validate($game->reveal(), $constraint->reveal());
    }

    public function testValidateWhenShipsNotSetYet()
    {
        $constraint = $this->prophesize(OnlyBeforeStart::class);
        $game = $this->prophesize(Game::class);
        $unitOfWork = $this->getUnitOfWork([]);

        $this->entityManager->getUnitOfWork()->willReturn($unitOfWork);
        $game->getPlayerNumber()->willReturn(1);
        $game->getPlayerShips()->willReturn([]);

        $this->validator->validate($game->reveal(), $constraint->reveal());
    }

    /**
     * @param array $changes
     * @return object
     */
    protected function getUnitOfWork(array $changes)//: object
    {
        return new class ($changes) {
            private $changes;

            public function __construct(array $changes)
            {
                $this->changes = $changes;
            }

            public function getEntityChangeSet($entity)
            {
                return $this->changes;
            }
        };
    }
}
