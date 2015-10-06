<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Exception\InvalidCoordinatesException;
use Doctrine\Common\Collections\Criteria;

class BattleManager
{
    const SHOT_RESULT_MISS = 'miss';
    const SHOT_RESULT_HIT = 'hit';
    const SHOT_RESULT_SUNK = 'sunk';

    /**
     * @todo replace with a constant (and composer update to require 5.6)
     * Array with Y axis elements
     * @var array
     */
    protected $axisY = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    /**
     * @todo replace with a constant (and composer update to require 5.6)
     * Array with X axis elements
     * @var array
     */
    protected $axisX = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];

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
     * @param Event $event
     * @return string miss|hit|sunk
     */
    public function getShotResult(Event $event)
    {
        $game = $event->getGame();
        $otherShips = $game->getOtherShips();
        $shot = $event->getValue();

        if (in_array($shot, $otherShips, true)) {
            $enemyShips = $game->getOtherShips();
            // @todo is there a nicer way to do it (getPlayerShots()), and do I need getEvents() method?
            $attackerShots = [];
            $shotEvents = $this->eventRepository->findForGameByTypeAndPlayer(
                $game,
                Event::TYPE_SHOT,
                $game->getPlayerNumber()
            );
            foreach ($shotEvents as $shotEvent) {
                $attackerShots[] = $shotEvent->getValue();
            }
            $result = $this->checkSunk(new CoordsInfo($shot), $enemyShips, $attackerShots)
                ? self::SHOT_RESULT_SUNK
                : self::SHOT_RESULT_HIT;
        } else {
            $result = self::SHOT_RESULT_MISS;
        }

        return $result;
    }

    /**
     * Finds which player's turn it is
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
     * @todo maybe CoordsInfo instead of shotCoords
     * Checks if the shot sinks the ship (if all other masts have been hit)
     *
     * @param CoordsInfo $coords Shot coordinates (Example: 'A1', 'B4', 'J10', ...)
     * @param array $enemyShips Attacked player ships
     * @param array $attackerShots Attacker shots
     * @param int $direction Direction which is checked for ship's masts
     * @return bool Whether the ship is sunk after this shot or not
     * @throws InvalidCoordinatesException
     */
    protected function checkSunk(CoordsInfo $coords, array $enemyShips, array $attackerShots, $direction = null)
    {
        $checkSunk = true;

        $sidePositions = $coords->getSidePositions();
        // try to find a mast which hasn't been hit
        foreach ($sidePositions as $key => $sidePosition) {
            // if no coordinate on this side (end of the board) or direction is specified,
            // but it's not the specified one
            if ($sidePosition === null || ($direction !== null && $direction !== $key)) {
                continue;
            }

            $sideCoords = $sidePosition->getCoords();
            $ship = array_search($sideCoords, $enemyShips);
            $shot = array_search($sideCoords, $attackerShots);

            // if there's a mast there and it's been hit, check this direction for more masts
            if ($ship !== false && $shot !== false) {
                $checkSunk = $this->checkSunk($sidePosition, $enemyShips, $attackerShots, $key);
            } elseif ($ship !== false) {
                // if mast hasn't been hit, the the ship can't be sunk
                $checkSunk = false;
            }


            if ($checkSunk === false) {
                break;
            }
        }

        return $checkSunk;
    }
}
