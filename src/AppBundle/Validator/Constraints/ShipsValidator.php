<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Battle\CoordsInfo;
use AppBundle\Battle\CoordsInfoCollection;
use AppBundle\Exception\InvalidShipsException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @todo take a look at refactoring maybe
 * @Annotation
 */
class ShipsValidator extends ConstraintValidator
{
    /**
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Ships) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\Ships', __NAMESPACE__));
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
        $shipsCollection = new CoordsInfoCollection($ships);
        $shipsCollection->sort();

        $this->validateShipsLength($shipsCollection);
        $this->validateEdgeConnections($shipsCollection);
        $this->validateShipsTypes($shipsCollection);
    }

    /**
     * @param CoordsInfoCollection $shipsCollection
     * @throws InvalidShipsException
     */
    protected function validateShipsLength(CoordsInfoCollection $shipsCollection)
    {
        // required number of masts
        $shipsLength = 20;

        // if the number of masts is correct
        if (count($shipsCollection) !== $shipsLength) {
            throw new InvalidShipsException('Number of ships\' masts is incorrect');
        }
    }

    /**
     * @param CoordsInfoCollection $shipsCollection
     * @throws InvalidShipsException
     */
    protected function validateEdgeConnections(CoordsInfoCollection $shipsCollection)
    {
        /** @var CoordsInfo $shipCoords */
        foreach ($shipsCollection as $shipCoords) {
            // if max to right
            if ($shipCoords->getRightPosition() === null) {
                continue;
            }

            // Enough to check one side corners, because I check all masts.
            // Checking right is more efficient because masts are sorted from the top left corner
            // B3 (index 12), upper right corner is A4 (index 03), so 12 - 3 = 9 -
            // second digit 0 is first row, so no upper corner
            $upperRightCorner = $shipsCollection->contains($shipCoords->getRightTopPosition()); // exists and in ships
            // B3 (index 12), lower right corner is C4 (index 23), so 23 - 12 = 11 -
            // second digit 9 is last row, so no lower corner
            $lowerRightCorner = $shipsCollection->contains($shipCoords->getRightBottomPosition()); // exists and in ships

            if ($upperRightCorner || $lowerRightCorner) {
                throw new InvalidShipsException('Ships\'s corners can\'t touch each other');
            }
        }
    }

    /**
     * @param CoordsInfoCollection $shipsCollection
     * @throws InvalidShipsException
     */
    protected function validateShipsTypes(CoordsInfoCollection $shipsCollection)
    {
        // @todo length, number of masts etc. to constant
        // sizes of ships to be count
        $shipsTypes = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        // B3 (index 12), going 2 down and 3 left is D6 (index 35), so 12 + (2 * 10) + (3 * 1) = 35
        $directionOffsets = [CoordsInfo::OFFSET_RIGHT, CoordsInfo::OFFSET_BOTTOM];

        $checkedCollection = new CoordsInfoCollection();
        /** @var CoordsInfo $shipCoords */
        foreach ($shipsCollection as $shipCoords) {
            // we ignore masts which have already been marked as a part of a ship
            if ($checkedCollection->contains($shipCoords)) {
                continue;
            }

            $shipLength = 1;
            foreach ($directionOffsets as $offset) {
                $checkCoords = $shipCoords->getOffsetCoords($offset);
                // check for masts until the battleground border is reached
                while ($shipsCollection->contains($checkCoords)) {
                    // ship is too long
                    if (++$shipLength > 4) {
                        throw new InvalidShipsException(sprintf('Ships can\'t have more than %s masts', 4));
                    }

                    // mark the mast as already checked
                    $checkedCollection->append($checkCoords);
                    $checkCoords = $checkCoords->getOffsetCoords($offset);
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
