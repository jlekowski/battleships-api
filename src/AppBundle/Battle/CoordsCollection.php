<?php

namespace AppBundle\Battle;

class CoordsCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $coordsArray = [];

    /**
     * @param array $coordsArray
     */
    public function __construct(array $coordsArray = [])
    {
        $this->coordsArray = $coordsArray;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->coordsArray;
    }

    /**
     * @param string $coords
     */
    public function append($coords)
    {
        $this->coordsArray[] = $coords;
    }

    /**
     * @param string $coords
     * @return bool
     */
    public function contains($coords)
    {
        return in_array($coords, $this->coordsArray, true);
    }

    /**
     * Sorts by coords (should be the same as sorting by position)
     */
    public function sort()
    {
        sort($this->coordsArray);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->coordsArray);
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->coordsArray);
    }
}
