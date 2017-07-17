<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Battle\CoordsCollection;
use AppBundle\Battle\CoordsManager;
use AppBundle\Exception\InvalidShipsException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class ShipsValidator extends ConstraintValidator
{
    const MAX_SHIP_LENGTH = 4;
    const TOTAL_LENGTH = 20; // required number of masts

    /**
     * @var CoordsManager
     */
    protected $coordsManager;

    /**
     * @param CoordsManager $coordsManager
     */
    public function __construct(CoordsManager $coordsManager)
    {
        $this->coordsManager = $coordsManager;
    }

    /**
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Ships) {
            throw new UnexpectedTypeException($constraint, Ships::class);
        }

        if (count($value) === 0) {
            return;
        }

        $this->validateShips($value);
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
    protected function validateShips(array $ships)
    {
        $shipsCollection = new CoordsCollection($ships);
        $shipsCollection->sort(); // required for validateEdgeConnections and validateShipsTypes

        $this->coordsManager->validateCoordsArray($shipsCollection->toArray());
        $this->validateShipsLength($shipsCollection);
        $this->validateEdgeConnections($shipsCollection);
        $this->validateShipsTypes($shipsCollection);
    }

    /**
     * @param CoordsCollection $shipsCollection
     * @throws InvalidShipsException
     */
    private function validateShipsLength(CoordsCollection $shipsCollection)
    {
        // if the number of masts is correct
        $mastCount = $shipsCollection->count();
        if ($mastCount !== self::TOTAL_LENGTH) {
            throw new InvalidShipsException(sprintf(
                'Number of ships\' masts is incorrect: %d (expected: %d)',
                $mastCount,
                self::TOTAL_LENGTH
            ));
        }
    }

    /**
     * @param CoordsCollection $shipsCollection
     * @throws InvalidShipsException
     */
    private function validateEdgeConnections(CoordsCollection $shipsCollection)
    {
        $rightOffsets = [CoordsManager::OFFSET_TOP_RIGHT, CoordsManager::OFFSET_BOTTOM_RIGHT];
        foreach ($shipsCollection as $shipCoords) {
            // if max to right
            if ($this->coordsManager->getByOffset($shipCoords, CoordsManager::OFFSET_RIGHT) === null) {
                continue;
            }

            // Enough to check one side corners, because I check all masts.
            // Checking right is more efficient because masts are sorted from the top left corner
            // B3 (index 12), top right corner is A4 (index 03), so 12 - 3 = 9 -
            // second digit 0 is first row, so no top corner
            // B3 (index 12), bottom right corner is C4 (index 23), so 23 - 12 = 11 -
            // second digit 9 is last row, so no bottom corner
            foreach ($rightOffsets as $rightOffset) {
                $offsetCoords = $this->coordsManager->getByOffset($shipCoords, $rightOffset);
                $offsetCoordsNotEmpty = $shipsCollection->contains($offsetCoords); // exists and in ships
                if ($offsetCoordsNotEmpty) {
                    throw new InvalidShipsException('Ships\'s corners can\'t touch each other');
                }
            }
        }
    }

    /**
     * @param CoordsCollection $shipsCollection
     * @throws InvalidShipsException
     */
    private function validateShipsTypes(CoordsCollection $shipsCollection)
    {
        // sizes of ships to be count
        $shipsTypes = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        // B3 (index 12), going 2 down and 3 left is D6 (index 35), so 12 + (2 * 10) + (3 * 1) = 35
        $directionOffsets = [CoordsManager::OFFSET_RIGHT, CoordsManager::OFFSET_BOTTOM];

        $checkedCoordsCollection = new CoordsCollection();
        foreach ($shipsCollection as $shipCoords) {
            // we ignore masts which have already been marked as a part of a ship
            if ($checkedCoordsCollection->contains($shipCoords)) {
                continue;
            }

            $shipLength = 1;
            foreach ($directionOffsets as $offset) {
                $checkCoords = $this->coordsManager->getByOffset($shipCoords, $offset);
                // check for masts until the battleground border is reached
                while ($shipsCollection->contains($checkCoords)) {
                    // ship is too long
                    if (++$shipLength > self::MAX_SHIP_LENGTH) {
                        throw new InvalidShipsException(sprintf('Ships can\'t have more than %d masts', self::MAX_SHIP_LENGTH));
                    }

                    // mark the mast as already checked
                    $checkedCoordsCollection->append($checkCoords);
                    $checkCoords = $this->coordsManager->getByOffset($checkCoords, $offset);
                }

                // masts found so don't look for more
                if ($shipLength > 1) {
                    break;
                }
            }

            $shipsTypes[$shipLength]++;
        }

        // whether the number of different ship types is correct
        $diff = array_diff_assoc($shipsTypes, [1 => 4, 2 => 3, 3 => 2, 4 => 1]);
        if (!empty($diff)) {
            throw new InvalidShipsException('Number of ships\' types is incorrect');
        }
    }
}
