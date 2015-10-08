<?php

namespace AppBundle\Battle;

use AppBundle\Exception\InvalidCoordinatesException;
use AppBundle\Exception\InvalidOffsetException;

/**
 * @todo rename get*Position to provide CoordsInfo
 * @todo maybe return CoordsInfoCollection, not array
 */
class CoordsInfo
{
    const OFFSET_TOP = 'TOP';
    const OFFSET_BOTTOM = 'BOTTOM';
    const OFFSET_RIGHT = 'RIGHT';
    const OFFSET_LEFT = 'LEFT';

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
     * @var string
     */
    protected $coordX;

    /**
     * @var string
     */
    protected $coordY;

    /**
     * @var int
     */
    protected $positionX;

    /**
     * @var int
     */
    protected $positionY;

    /**
     * @param string|array $coords Coordinates or Position (e.g.: 'A1', [0,0], 'B4', [1,3], 'J10', [9,9], ...)
     * @throws InvalidCoordinatesException
     */
    public function __construct($coords)
    {
        if (!$coords) {
            throw new InvalidCoordinatesException($coords);
        }

        if (is_array($coords)) {
            $this->populateDataFromPosition($coords);
        } else {
            $this->populateDataFromCoords($coords);
        }

        if ($this->positionY === false || $this->positionX === false) {
            throw new InvalidCoordinatesException($coords);
        }
    }

    /**
     * @return string
     */
    public function getCoords()
    {
        return $this->getCoordY() . $this->getCoordX();
    }

    /**
     * @return string
     */
    public function getCoordX()
    {
        return $this->coordX;
    }

    /**
     * @return string
     */
    public function getCoordY()
    {
        return $this->coordY;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->getPositionY() . $this->getPositionX();
    }

    /**
     * @return int
     */
    public function getPositionX()
    {
        return $this->positionX;
    }

    /**
     * @return int
     */
    public function getPositionY()
    {
        return $this->positionY;
    }

    /**
     * Neighbour coordinates, taking into consideration edge positions (A and J rows, 1 and 10 columns)
     *
     * @return CoordsInfo[]
     */
    public function getSidePositions()
    {
        return [
            self::OFFSET_TOP => $this->getTopPosition(),
            self::OFFSET_BOTTOM =>$this->getBottomPosition(),
            self::OFFSET_RIGHT =>$this->getRightPosition(),
            self::OFFSET_LEFT =>$this->getLeftPosition()
        ];
    }

    /**
     * @return CoordsInfo[]
     */
    public function getCornerPositions()
    {
        return [
            $this->getLeftTopPosition(),
            $this->getRightTopPosition(),
            $this->getLeftBottomPosition(),
            $this->getRightBottomPosition()
        ];
    }

    /**
     * @return CoordsInfo[]
     */
    public function getSurroundingPositions()
    {
        return [
            $this->getLeftPosition(),
            $this->getRightPosition(),
            $this->getTopPosition(),
            $this->getBottomPosition(),
            $this->getLeftTopPosition(),
            $this->getRightTopPosition(),
            $this->getLeftBottomPosition(),
            $this->getRightBottomPosition()
        ];
    }

    /**
     * @return CoordsInfo|null
     */
    public function getTopPosition()
    {
        return $this->getPositionY() > 0
            ? new CoordsInfo([$this->getPositionY() - 1, $this->getPositionX()])
            : null;
    }

    /**
     * @return CoordsInfo|null
     */
    public function getBottomPosition()
    {
        return $this->getPositionY() < 9
            ? new CoordsInfo([$this->getPositionY() + 1, $this->getPositionX()])
            : null;
    }

    /**
     * @return CoordsInfo|null
     */
    public function getRightPosition()
    {
        return $this->getPositionX() < 9
            ? new CoordsInfo([$this->getPositionY(), $this->getPositionX() + 1])
            : null;
    }

    /**
     * @return CoordsInfo|null
     */
    public function getLeftPosition()
    {
        return $this->getPositionX() > 0
            ? new CoordsInfo([$this->getPositionY(), $this->getPositionX() - 1])
            : null;
    }

    /**
     * @return CoordsInfo|null
     */
    public function getRightTopPosition()
    {
        return ($this->getPositionY() > 0) && ($this->getPositionX() < 9)
            ? new CoordsInfo([$this->getPositionY() - 1, $this->getPositionX() + 1])
            : null;
    }

    /**
     * @return CoordsInfo|null
     */
    public function getLeftTopPosition()
    {
        return ($this->getPositionY() > 0) && ($this->getPositionX() > 0)
            ? new CoordsInfo([$this->getPositionY() - 1, $this->getPositionX() - 1])
            : null;
    }

    /**
     * @return CoordsInfo|null
     */
    public function getRightBottomPosition()
    {
        return ($this->getPositionY() < 9) && ($this->getPositionX() < 9)
            ? new CoordsInfo([$this->getPositionY() + 1, $this->getPositionX() + 1])
            : null;
    }

    /**
     * @return CoordsInfo|null
     */
    public function getLeftBottomPosition()
    {
        return ($this->getPositionY() < 9) && ($this->getPositionX() > 0)
            ? new CoordsInfo([$this->getPositionY() + 1, $this->getPositionX() - 1])
            : null;
    }

    /**
     * @param string $offset
     * @return CoordsInfo|null
     * @throws InvalidOffsetException
     */
    public function getOffsetCoords($offset)
    {
        switch ($offset) {
            case self::OFFSET_TOP:
                return $this->getTopPosition();

            case self::OFFSET_BOTTOM:
                return $this->getBottomPosition();

            case self::OFFSET_RIGHT:
                return $this->getRightPosition();

            case self::OFFSET_LEFT:
                return $this->getLeftPosition();

            default:
                throw new InvalidOffsetException($offset);
        }
    }

    /**
     * @param string $coords
     */
    protected function populateDataFromCoords($coords)
    {
        $this->coordY = $coords[0];
        $this->coordX = substr($coords, 1);
        $this->positionY = array_search($this->coordY, $this->axisY);
        $this->positionX = array_search($this->coordX, $this->axisX);
    }

    /**
     * @param array $position
     */
    protected function populateDataFromPosition(array $position)
    {
        $this->positionY = $position[0];
        $this->positionX = $position[1];
        $this->coordY = $this->axisY[$this->positionY];
        $this->coordX = $this->axisX[$this->positionX];
    }
}
