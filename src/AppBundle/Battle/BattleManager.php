<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Exception\InvalidCoordinatesException;

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
            // @todo of course I need to get only shots for the player, not everything (do I need getEvents() method?)
            $attackerShots = [];
            $shotEvents = $this->eventRepository->findBy([
                'game' => $game,
                'type' => Event::TYPE_SHOT,
                'player' => $game->getPlayerNumber()
            ]);
            foreach ($shotEvents as $shotEvent) {
                $attackerShots[] = $shotEvent->getValue();
            }
            $result = $this->checkSunk($shot, $enemyShips, $attackerShots)
                ? self::SHOT_RESULT_SUNK
                : self::SHOT_RESULT_HIT;
        } else {
            $result = self::SHOT_RESULT_MISS;
        }

        return $result;
    }

    /**
     * @todo maybe CoordsInfo instead of shotCoords
     * Checks if the shot sinks the ship (if all other masts have been hit)
     *
     * @param string $shotCoords Shot coordinates (Example: 'A1', 'B4', 'J10', ...)
     * @param array $enemyShips Attacked player ships
     * @param array $attackerShots Attacker shots
     * @param int $direction Direction which is checked for ship's masts
     * @return bool Whether the ship is sunk after this shot or not
     * @throws InvalidCoordinatesException
     */
    protected function checkSunk($shotCoords, array $enemyShips, array $attackerShots, $direction = null)
    {
        $coordsInfo = new CoordsInfo($shotCoords);
        // neighbour coordinates, taking into consideration edge positions (A and J rows, 1 and 10 columns)
        $sunkCoords = $coordsInfo->getSunkCoords();

        $checkSunk = true;
        // try to find a mast which hasn't been hit
        foreach ($sunkCoords as $key => $value) {
            // if no coordinate on this side (end of the board) or direction is specified,
            // but it's not the specified one
            if ($value === null || ($direction !== null && $direction !== $key)) {
                continue;
            }

            $ship = array_search($value, $enemyShips);
            $shot = array_search($value, $attackerShots);

            // if there's a mast there and it's been hit, check this direction for more masts
            if ($ship !== false && $shot !== false) {
                $checkSunk = $this->checkSunk($value, $enemyShips, $attackerShots, $key);
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
