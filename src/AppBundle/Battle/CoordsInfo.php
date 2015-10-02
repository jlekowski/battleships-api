<?php

namespace AppBundle\Battle;

use AppBundle\Exception\InvalidCoordinatesException;

class CoordsInfo
{
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
     * @param string $coords Coordinates (Example: 'A1', 'B4', 'J10', ...)
     * @throws InvalidCoordinatesException
     */
    public function __construct($coords)
    {
        if (!$coords) {
            throw new InvalidCoordinatesException($coords);
        }

        $this->coordX = $coords[0];
        $this->coordY = substr($coords, 1);
        $this->positionY = array_search($this->coordY, $this->axisY);
        $this->positionX = array_search($this->coordX, $this->axisX);

        if ($this->positionY === false || $this->positionX === false) {
            throw new InvalidCoordinatesException($coords);
        }
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
     * @todo add comment and rename to be something more about edges/sides or something
     * @return array
     */
    public function getSunkCoords()
    {
        return [
            $this->getPositionY() > 0 ? $this->axisY[$this->getPositionY() - 1] . $this->getCoordX() : null,
            $this->getPositionY() < 9 ? $this->axisY[$this->getPositionY() + 1] . $this->getCoordX() : null,
            $this->getPositionX() < 9 ? $this->getCoordY() . $this->axisX[$this->getPositionX() + 1] : null,
            $this->getPositionX() > 0 ? $this->getCoordY() . $this->axisX[$this->getPositionX() - 1] : null
        ];
    }
}
