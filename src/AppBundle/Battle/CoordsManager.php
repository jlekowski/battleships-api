<?php

namespace AppBundle\Battle;

use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidOffsetException;
use AppBundle\Exception\InvalidShipsException;

class CoordsManager
{
    const OFFSET_TOP = 'TOP';
    const OFFSET_BOTTOM = 'BOTTOM';
    const OFFSET_RIGHT = 'RIGHT';
    const OFFSET_LEFT = 'LEFT';
    const OFFSET_TOP_RIGHT = 'TOP_RIGHT';
    const OFFSET_TOP_LEFT = 'TOP_LEFT';
    const OFFSET_BOTTOM_RIGHT = 'BOTTOM_RIGHT';
    const OFFSET_BOTTOM_LEFT = 'BOTTOM_LEFT';
    /** Array with Y axis elements */
    /* protected */ const AXIS_Y = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    /** Array with X axis elements */
    /* protected */ const AXIS_X = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];

    /**
     * @param string $coords
     * @param string $offset
     * @return string|null
     * @throws InvalidShipsException
     */
    public function getByOffset($coords, $offset)
    {
        switch ($offset) {
            case self::OFFSET_TOP:
                $offsetIndexes = [-1, 0];
                break;

            case self::OFFSET_BOTTOM:
                $offsetIndexes = [1, 0];
                break;

            case self::OFFSET_RIGHT:
                $offsetIndexes = [0, 1];
                break;

            case self::OFFSET_LEFT:
                $offsetIndexes = [0, -1];
                break;

            case self::OFFSET_TOP_RIGHT:
                $offsetIndexes = [-1, 1];
                break;

            case self::OFFSET_TOP_LEFT:
                $offsetIndexes = [-1, -1];
                break;

            case self::OFFSET_BOTTOM_RIGHT:
                $offsetIndexes = [1, 1];
                break;

            case self::OFFSET_BOTTOM_LEFT:
                $offsetIndexes = [1, -1];
                break;

            default:
                throw new InvalidOffsetException($offset);
        }

        return $this->findCoordsOffset($coords, $offsetIndexes);
    }

    /**
     * @param array $offsets
     * @param string $coords
     * @return array
     * @throws InvalidShipsException
     */
    public function getByOffsets($coords, array $offsets)
    {
        $offsetCoords = [];
        foreach ($offsets as $offset) {
            $offsetCoords[$offset] = $this->getByOffset($coords, $offset);
        }

        return $offsetCoords;
    }

    /**
     * @param string $coords
     * @throws InvalidCoordinatesException
     */
    public function validateCoords($coords)
    {
        $this->coordsToPositions($coords);
    }

    /**
     * @param array $coordsArray
     * @throws InvalidCoordinatesException
     */
    public function validateCoordsArray(array $coordsArray)
    {
        foreach ($coordsArray as $coords) {
            $this->validateCoords($coords);
        }
    }

    /**
     * @param string $coords
     * @param array $offsetIndexes
     * @return string|null
     * @throws InvalidCoordinatesException
     */
    protected function findCoordsOffset($coords, array $offsetIndexes)
    {
        list($positionY, $positionX) = $this->coordsToPositions($coords);
        $newPositionY = $positionY + $offsetIndexes[0];
        $newPositionX = $positionX + $offsetIndexes[1];

        return array_key_exists($newPositionY, self::AXIS_Y) && array_key_exists($newPositionX, self::AXIS_X)
            ? self::AXIS_Y[$newPositionY] . self::AXIS_X[$newPositionX]
            : null;
    }

    /**
     * @param string $coords
     * @return array
     * @throws InvalidCoordinatesException
     */
    private function coordsToPositions($coords)
    {
        $positionY = $positionX = false;
        // string with at least 2 characters
        if (is_string($coords) && isset($coords[1])) {
            $coordY = $coords[0];
            $coordX = substr($coords, 1);
            $positionY = array_search($coordY, self::AXIS_Y);
            $positionX = array_search($coordX, self::AXIS_X);
        }

        if ($positionY === false || $positionX === false) {
            throw new InvalidCoordinatesException($coords);
        }

        return [$positionY, $positionX];
    }
}
