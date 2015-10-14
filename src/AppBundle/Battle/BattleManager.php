<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Exception\DuplicatedEventTypeException;
use AppBundle\Exception\GameFlowException;
use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidShipsException;
use AppBundle\Exception\UnexpectedEventTypeException;

class BattleManager
{
    const SHOT_RESULT_MISS = 'miss';
    const SHOT_RESULT_HIT = 'hit';
    const SHOT_RESULT_SUNK = 'sunk';

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
     * @todo maybe pass $shot and $otherShips, but then how to get shots?
     * @param Event $shotEvent
     * @return string miss|hit|sunk
     */
    public function getShotResult(Event $shotEvent)
    {
        if ($shotEvent->getType() !== Event::TYPE_SHOT) {
            throw new UnexpectedEventTypeException($shotEvent->getType(), Event::TYPE_SHOT);
        }

        $game = $shotEvent->getGame();
        $enemyShips = new CoordsInfoCollection($game->getOtherShips());
        $shot = new CoordsInfo($shotEvent->getValue());

        if ($enemyShips->contains($shot)) {
            // @todo is there a nicer way to do it (getPlayerShots()), and do I need getEvents() method?
            $attackerShots = $this->getAttackerShots($shotEvent);
            $result = $this->isSunk($shot, $enemyShips, $attackerShots)
                ? self::SHOT_RESULT_SUNK
                : self::SHOT_RESULT_HIT;
        } else {
            $result = self::SHOT_RESULT_MISS;
        }

        return $result;
    }

    /**
     * Finds which player's turn it is
     * @param Game $game
     * @return int
     */
    public function whoseTurn(Game $game)
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
     * @todo remove this method and just use ShipsValidator
     * @param array $ships
     * @throws InvalidShipsException
     */
    public function validateShips(array $ships)
    {
        $shipValidator = new ShipValidator();
        $shipValidator->validateAll($ships);
    }

    /**
     * @param Event $event
     * @throws GameFlowException
     * @throws InvalidCoordinatesException
     */
    public function validateShootingNow(Event $event)
    {
        $game = $game = $event->getGame();
        $hasOtherStarted = $this->doesEventExistForPlayer($game, Event::TYPE_START_GAME, $game->getOtherNumber());
        if (!$hasOtherStarted) {
            throw new GameFlowException('Other player has not started yet');
        }

        $isPlayerTurn = $this->whoseTurn($game) === $game->getPlayerNumber();
        if (!$isPlayerTurn) {
            throw new GameFlowException('It\'s other player\'s turn');
        }

        // @todo some better way to validate
        new CoordsInfo($event->getValue());
    }

    /**
     * @param Event $event
     * @throws DuplicatedEventTypeException
     */
    public function validateAddingUniqueEvent(Event $event)
    {
        $game = $event->getGame();
        $eventExists = $this->doesEventExistForPlayer($game, $event->getType(), $game->getPlayerNumber());

        if ($eventExists) {
            throw new DuplicatedEventTypeException($event->getType());
        }
    }

    /**
     * Checks if the shot sinks the ship (if all other masts have been hit)
     *
     * @param CoordsInfo $mast
     * @param CoordsInfoCollection $allShips
     * @param CoordsInfoCollection $allShots
     * @return bool
     * @throws InvalidCoordinatesException
     */
    public function isSunk(CoordsInfo $mast, CoordsInfoCollection $allShips, CoordsInfoCollection $allShots)
    {
        $sidePositions = $mast->getSidePositions();
        // try to find a mast which hasn't been hit
        foreach ($sidePositions as $offset => $sidePosition) {
            if ($sidePosition
                && $this->remainingMastInDirectionExists($sidePosition, $allShips, $allShots, $offset)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CoordsInfo $mast
     * @param CoordsInfoCollection $allShips
     * @param CoordsInfoCollection $allShots
     * @param string $offset
     * @return bool
     */
    private function remainingMastInDirectionExists(
        CoordsInfo $mast,
        CoordsInfoCollection $allShips,
        CoordsInfoCollection $allShots,
        $offset
    ) {
        while ($allShips->contains($mast)) {
            if (!$allShots->contains($mast)) {
                return true;
            }
            $mast = $mast->getOffsetCoords($offset);
        }

        return false;
    }

    /**
     * @param Event $event
     * @return CoordsInfoCollection
     */
    private function getAttackerShots(Event $event)
    {
        $shotEvents = $this->eventRepository->findForGameByTypeAndPlayer(
            $event->getGame(),
            Event::TYPE_SHOT,
            $event->getPlayer()
        );

        $attackerShots = [];
        foreach ($shotEvents as $shotEvent) {
            $attackerShots[] = $shotEvent->getValue();
        }

        return new CoordsInfoCollection($attackerShots);
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
