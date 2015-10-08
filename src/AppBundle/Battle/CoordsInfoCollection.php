<?php

namespace AppBundle\Battle;

use AppBundle\Exception\InvalidCoordinatesException;

class CoordsInfoCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var CoordsInfo[]
     */
    protected $coordsInfoArray = [];

    /**
     * @param array $coordsArray
     * @throws InvalidCoordinatesException
     */
    public function __construct(array $coordsArray = [])
    {
        // array_map doesn't like exceptions in callback, so @array_map() otherwise
        foreach ($coordsArray as $coords) {
            $this->coordsInfoArray[] = new CoordsInfo($coords);
        }
    }

    /**
     * Sorts the coordsInfoArray by position
     */
    public function sort()
    {
        usort($this->coordsInfoArray, function (CoordsInfo $coords1, CoordsInfo $coords2) {
            return $coords1->getPosition() < $coords2->getPosition() ? -1 : 1;
        });
    }

    /**
     * @todo maybe always return object with isEmpty() === true instead of null?
     * @todo cache all coords? But what if appended?
     * @param CoordsInfo $searchedCoords
     * @return bool
     */
    public function contains(CoordsInfo $searchedCoords = null)
    {
        if ($searchedCoords !== null) {
            foreach ($this->coordsInfoArray as $coordsInfo) {
                if ($coordsInfo->getCoords() === $searchedCoords->getCoords()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param CoordsInfo $coordsInfo
     */
    public function append(CoordsInfo $coordsInfo)
    {
        $this->coordsInfoArray[] = $coordsInfo;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->coordsInfoArray);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->coordsInfoArray);
    }
}
