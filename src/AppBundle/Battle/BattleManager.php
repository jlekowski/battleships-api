<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Event;
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
     * @param Event $event
     * @return string miss|hit|sunk
     */
    public function getShotResult(Event $event)
    {
        $otherShips = $event->getGame()->getOtherShips();
        $shot = $event->getValue();

        if (in_array($shot, $otherShips, true)) {
            $result = $this->checkSunk($shot) ? self::SHOT_RESULT_SUNK : self::SHOT_RESULT_HIT;
        } else {
            $result = self::SHOT_RESULT_MISS;
        }

        return $result;
    }

    /**
     * Checks if the shot sinks the ship (if all other masts have been hit)
     *
     * @param string $coords Shot coordinates (Example: 'A1', 'B4', 'J10', ...)
     * @param string $shooter Whose shot is about to be checked (player|other)
     * @param int $direction Direction which is checked for ship's masts
     * @return bool Whether the ship is sunk after this shot or not
     * @throws \InvalidArgumentException
     * @throws InvalidCoordinatesException
     */
    protected function checkSunk($coords, $shooter = 'player', $direction = null)
    {
        if (!in_array($shooter, array('player', 'other'))) {
            throw new \InvalidArgumentException(sprintf('Incorrect shooter (%s)', $shooter));
        }

        $coordsInfo = new CoordsInfo($coords);
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

            $ships = $shooter == 'player' ? $this->oData->getOtherShips()  : $this->oData->getPlayerShips();
            $shots = $shooter == 'player' ? $this->oData->getPlayerShots() : $this->oData->getOtherShots();
            $ship = array_search($value, $ships);
            $shot = array_search($value, $shots);

            // if there's a mast there and it's been hit, check this direction for more masts
            if ($ship !== false && $shot !== false) {
                $checkSunk = $this->checkSunk($value, $shooter, $key);
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
