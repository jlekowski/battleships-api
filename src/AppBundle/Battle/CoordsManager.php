<?php

namespace AppBundle\Battle;

use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidOffsetException;

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

    /**
     * Array with Y axis elements
     * @var array
     */
    protected $axisY = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    /**
     * Array with X axis elements
     * @var array
     */
    protected $axisX = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];

    /**
     * @param string $coords
     * @param string $offset
     * @return string|null
     * @throws InvalidOffsetException
     */
    public function getByOffset($coords, $offset)
    {
        switch ($offset) {
            case self::OFFSET_TOP:
                $offsets = [-1, 0];
                break;

            case self::OFFSET_BOTTOM:
                $offsets = [1, 0];
                break;

            case self::OFFSET_RIGHT:
                $offsets = [0, 1];
                break;

            case self::OFFSET_LEFT:
                $offsets = [0, -1];
                break;

            case self::OFFSET_TOP_RIGHT:
                $offsets = [-1, 1];
                break;

            case self::OFFSET_TOP_LEFT:
                $offsets = [-1, -1];
                break;

            case self::OFFSET_BOTTOM_RIGHT:
                $offsets = [1, 1];
                break;

            case self::OFFSET_BOTTOM_LEFT:
                $offsets = [1, -1];
                break;

            default:
                throw new InvalidOffsetException($offset);
        }

        return $this->findCoordsOffset($coords, $offsets);
    }

    /**
     * @param array $offsets
     * @param string $coords
     * @return array
     * @throws InvalidOffsetException
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
        list($positionY, $positionX) = $this->coordsToPositions($coords);
        if ($positionY === false || $positionX === false) {
            throw new InvalidCoordinatesException($coords);
        }
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
     * @param array $offsets
     * @return string
     */
    protected function findCoordsOffset($coords, array $offsets = [])
    {
        list($positionY, $positionX) = $this->coordsToPositions($coords);
        $newPositionY = $positionY + $offsets[0];
        $newPositionX = $positionX + $offsets[1];

        return isset($this->axisY[$newPositionY]) && isset($this->axisX[$newPositionX])
            ? $this->axisY[$newPositionY] . $this->axisX[$newPositionX]
            : null;
    }

    /**
     * @param string $coords
     * @return array
     */
    private function coordsToPositions($coords)
    {
        $coordY = $coords[0];
        $coordX = substr($coords, 1);
        $positionY = array_search($coordY, $this->axisY);
        $positionX = array_search($coordX, $this->axisX);

        return [$positionY, $positionX];
    }
}
