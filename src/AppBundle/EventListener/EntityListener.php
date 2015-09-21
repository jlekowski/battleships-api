<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Game;
use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidShipsException;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;

class EntityListener
{
    /**
     * @var UnitOfWork
     */
    protected $unitOfWork;

    /**
     * @param PreUpdateEventArgs $eventArgs
     * @throws InvalidShipsException
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $this->unitOfWork = $entityManager->getUnitOfWork();

        foreach ($this->unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Game) {
                throw new InvalidCoordinatesException('K11');
                $this->handleGameUpdate($entity);
            }
        }
    }

    /**
     * @param Game $game
     */
    private function handleGameUpdate(Game $game)
    {
        $changes = $this->unitOfWork->getEntityChangeSet($game);
        foreach ($changes as $property => $diff) {
            switch ($property) {
                case 'player1Ships':
                case 'player2Ships':
                    $this->validateShips($diff[1]);
                    break;
            }
        }
    }

    /**
     * Checks if ships are set correctly
     *
     * Validates coordinates of all ships' masts, checks the number,
     *     sizes and shapes of the ships, and potential edge connections between them.
     *
     * @param array $ships Ships set by the player (Example: 'A1,B4,J10,...')
     * @throws InvalidShipsException
     */
    private function validateShips(array $ships)
    {
        // Standard coordinates are converted to indexes, e.g. 'A1' -> '00', 'B3' -> '12', 'J10' -> '99'
        $toIndex = function ($coords) {
            $coordsInfo = $this->coordsInfo($coords);

            return $coordsInfo['position_y'] . $coordsInfo['position_x'];
        };
        // array_map doesn't like exceptions in callback
        $shipsArray = @array_map($toIndex, $ships);
        sort($shipsArray);

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
        foreach ($shipsArray as $key => $index) {
            if ($index[0] == 9) {
                continue;
            }

            // Enough to check one side corners, because I check all masts.
            // Checking right is more efficient because masts are sorted from the top left corner
            // B3 (index 12), upper right corner is A4 (index 03), so 12 - 3 = 9 -
            // second digit 0 is first row, so no upper corner
            $upperRightCorner = ($index[1] > 0) && (in_array($index + 9, $shipsArray));
            // B3 (index 12), lower right corner is C4 (index 23), so 23 - 12 = 11 -
            // second digit 9 is last row, so no lower corner
            $lowerRightCorner = ($index[1] < 9) && (in_array($index + 11, $shipsArray));

            if ($upperRightCorner || $lowerRightCorner) {
                throw new InvalidShipsException('Ships\'s corners can\'t touch each other');
            }
        }

        $masts = [];

        // check if there are the right types of ships
        foreach ($shipsArray as $key => $index) {
            // we ignore masts which have already been marked as a part of a ship
            if (array_key_exists($index, $masts)) {
                continue;
            }

            foreach ($directionMultipliers as $k => $multiplier) {
                $axisIndex = $k == 1 ? 0 : 1;
                $boardOffset = $index[$axisIndex];

                $shipType = 1;
                // check for masts until the battleground border is reached
                while ($boardOffset + $shipType <= 9) {
                    $checkIndex = sprintf('%02s', $index + ($shipType * $multiplier));

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
     * Array with Y axis elements
     * @var array
     */
    public static $axisY = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    /**
     * Array with X axis elements
     * @var array
     */
    public static $axisX = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];

    /**
     * Gives the detailed information about the coordinates
     *
     * Standard coordinates are split into Y and X axis values and appended with the index information.
     *
     * Example: 'B3' -> ['coord_y' => 'B', 'coord_x' => '3', 'position_y' => 1, 'position_x' => 2]
     *
     * @param string $coords Coordinates (Example: 'A1', 'B4', 'J10', ...)
     * @return array Split coordinates (Y and X) and indexes (Y and X)
     * @throws InvalidCoordinatesException
     */
    private function coordsInfo($coords)
    {
        if (!$coords) {
            throw new InvalidCoordinatesException($coords);
        }

        $coordY = $coords[0];
        $coordX = substr($coords, 1);

        $positionY = array_search($coordY, self::$axisY);
        $positionX = array_search($coordX, self::$axisX);

        if ($positionY === false || $positionX === false) {
            throw new InvalidCoordinatesException($coords);
        }


        $coordsInfo = [
            'coord_y' => $coordY,
            'coord_x' => $coordX,
            'position_y' => $positionY,
            'position_x' => $positionX
        ];

        return $coordsInfo;
    }


    private function checkGameUpdates(Game $game)
    {
//        $game = $this->gameRepository->find($id);
//        $game->setPlayer2Name('aaa');
        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets();
        echo "<pre>";
        var_dump($uow->getEntityChangeSet($game));
        exit;
    }
}
