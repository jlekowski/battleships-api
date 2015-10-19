<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Exception\GameFlowException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class IsAllowedToShootValidator extends ConstraintValidator
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @param EventRepository $eventRepository
     */
    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsAllowedToShoot) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\IsAllowedToShoot', __NAMESPACE__));
        }

        if (!$value instanceof Event) {
            throw new UnexpectedTypeException($value, 'AppBundle\Entity\Event');
        }

        if ($value->getType() !== Event::TYPE_SHOT) {
            return;
        }

        $this->validateShootingNow($value->getGame());
    }

    /**
     * @param Game $game
     * @throws GameFlowException
     */
    public function validateShootingNow(Game $game)
    {
        $hasOtherStarted = $this->doesEventExistForPlayer($game, Event::TYPE_START_GAME, $game->getOtherNumber());
        if (!$hasOtherStarted) {
            throw new GameFlowException('Other player has not started yet');
        }

        $isPlayerTurn = $this->whoseTurn($game) === $game->getPlayerNumber();
        if (!$isPlayerTurn) {
            throw new GameFlowException('It\'s other player\'s turn');
        }
    }

    /**
     * Finds which player's turn it is
     * @param Game $game
     * @return int 1|2
     */
    private function whoseTurn(Game $game)
    {
        $event = $this->eventRepository->findLastForGameByType($game, Event::TYPE_SHOT);

        if (empty($event)) {
            $whoseTurn = 1;
        } elseif ($event->getPlayer() === $game->getPlayerNumber()) {
            $whoseTurn = in_array($event->getValue(), $game->getOtherShips())
                ? $game->getPlayerNumber()
                : $game->getOtherNumber();
        } else {
            $whoseTurn = in_array($event->getValue(), $game->getPlayerShips())
                ? $game->getOtherNumber()
                : $game->getPlayerNumber();
        }

        return $whoseTurn;
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @param int $playerNumber
     * @return bool
     */
    private function doesEventExistForPlayer(Game $game, $eventType, $playerNumber)
    {
        $events = $this->eventRepository->findForGameByTypeAndPlayer($game, $eventType, $playerNumber);

        return !$events->isEmpty();
    }
}
