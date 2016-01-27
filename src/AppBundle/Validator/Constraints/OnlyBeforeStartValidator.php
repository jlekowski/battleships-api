<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Entity\Game;
use AppBundle\Exception\GameFlowException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class OnlyBeforeStartValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof OnlyBeforeStart) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\OnlyBeforeStart', __NAMESPACE__));
        }

        if (!$value instanceof Game) {
            throw new UnexpectedTypeException($value, 'AppBundle\Entity\Game');
        }

        if ($this->hasGameAlreadyStarted($value)) {
            throw new GameFlowException('Ships can\'t be changed - game has already started');
        }
    }

    /**
     * @param Game $game
     * @return bool
     */
    protected function hasGameAlreadyStarted(Game $game)
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $changes = $unitOfWork->getEntityChangeSet($game);
        $changeFieldKey = sprintf('user%dShips', $game->getPlayerNumber());

        // old ships if changed, or current ships
        $playerShipsBeforeChanges = isset($changes[$changeFieldKey])
            ? $changes[$changeFieldKey][0]
            : $game->getPlayerShips();

        // game started if ships already set
        return count($playerShipsBeforeChanges) > 0;
    }
}
