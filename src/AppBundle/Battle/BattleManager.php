<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidShipsException;
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
     * Checks if ships are set correctly
     *
     * Validates coordinates of all ships' masts, checks the number,
     *     sizes and shapes of the ships, and potential edge connections between them.
     *
     * @param array $ships Ships set by the player (Example: ['A1','B4','J10',...])
     * @throws InvalidShipsException
     */
    public function validateShips(array $ships)
    {
        // Standard coordinates are converted to indexes, e.g. 'A1' -> '00', 'B3' -> '12', 'J10' -> '99'
        $toCoordsInfo = function ($coords) {
            return new CoordsInfo($coords);
        };
        // array_map doesn't like exceptions in callback
        $shipsArray = @array_map($toCoordsInfo, $ships);

        $sortCoords = function(CoordsInfo $coords1, CoordsInfo $coords2) {
            return $coords1->getPosition() < $coords2->getPosition() ? -1 : 1;
        };
        usort($shipsArray, $sortCoords);

        // required number of masts
        $shipsLength = 20;
        // sizes of ships to be count
        $shipsTypes = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        // B3 (index 12), going 2 down and 3 left is D6 (index 35), so 12 + (2 * 10) + (3 * 1) = 35
        $directionMultipliers = [1, 10];

        // if the number of masts is correct
        if (count($shipsArray) !== $shipsLength) {
            throw new InvalidShipsException('Number of ships\' masts is incorrect');
        }


        // check if no edge connection
        foreach ($shipsArray as $shipCoords) {
            if ($shipCoords[0] == 9) {
                continue;
            }

            // Enough to check one side corners, because I check all masts.
            // Checking right is more efficient because masts are sorted from the top left corner
            // B3 (index 12), upper right corner is A4 (index 03), so 12 - 3 = 9 -
            // second digit 0 is first row, so no upper corner
            $upperRightCorner = ($shipCoords[1] > 0) && (in_array($shipCoords + 9, $shipsArray));
            // B3 (index 12), lower right corner is C4 (index 23), so 23 - 12 = 11 -
            // second digit 9 is last row, so no lower corner
            $lowerRightCorner = ($shipCoords[1] < 9) && (in_array($shipCoords + 11, $shipsArray));

            if ($upperRightCorner || $lowerRightCorner) {
                throw new InvalidShipsException('Ships\'s corners can\'t touch each other');
            }
        }

        $masts = [];

        // check if there are the right types of ships
        foreach ($shipsArray as $shipCoords) {
            // we ignore masts which have already been marked as a part of a ship
            if (array_key_exists($shipCoords, $masts)) {
                continue;
            }

            foreach ($directionMultipliers as $k => $multiplier) {
                $axisIndex = $k == 1 ? 0 : 1;
                $boardOffset = $shipCoords[$axisIndex];

                $shipType = 1;
                // check for masts until the battleground border is reached
                while ($boardOffset + $shipType <= 9) {
                    $checkIndex = sprintf('%02s', $shipCoords + ($shipType * $multiplier));

                    // no more masts
                    if (!in_array($checkIndex, $shipsArray)) {
                        break;
                    }

                    // mark the mast as already checked
                    $masts[$checkIndex] = true;

                    // ship is too long
                    if (++$shipType > 4) {
                        throw new InvalidShipsException('Ship can\'t have more than four masts');
                    }
                }

                // if not masts found and more directions to check
                if (($shipType == 1) && ($k + 1 != count($directionMultipliers))) {
                    continue;
                }

                break; // either all (both) directions checked or the ship is found
            }

            $shipsTypes[$shipType]++;
        }

        // whether the number of different ship types is correct
        $diff = array_diff_assoc($shipsTypes, [1 => 4, 2 => 3, 3 => 2, 4 => 1]);
        if (!empty($diff)) {
            throw new InvalidShipsException('Number of ships\' types is incorrect');
        }
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
