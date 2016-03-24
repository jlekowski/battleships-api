<?php

namespace Tests\AppBundle\Battle;

use AppBundle\Battle\CoordsCollection;

class CoordsCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorDoesNotThrowExceptionWhenAnyCoordsInvalid()
    {
        $coordsCollection = new CoordsCollection(['A1', 'B2', 'J10', 'A11']);
        $this->assertInstanceOf('\AppBundle\Battle\CoordsCollection', $coordsCollection);
    }

    public function testClassIsTraversable()
    {
        $coordsArray = ['A1', 'B2', 'J10', 'A10'];
        $coordsCollection = new CoordsCollection($coordsArray);

        foreach ($coordsCollection as $key => $coords) {
            $this->assertEquals($coordsArray[$key], $coords);
        }
    }

    public function testToArray()
    {
        $coordsArray = ['A1', 'B2', 'J10', 'A10'];
        $coordsCollection = new CoordsCollection($coordsArray);

        $this->assertEquals($coordsArray, $coordsCollection->toArray());
    }

    public function testSort()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsCollection = new CoordsCollection($coordsArray);
        $coordsCollection->sort();

        $coordsArraySorted = ['A1', 'A10', 'B2', 'J1', 'J10'];
        foreach ($coordsCollection as $key => $coords) {
            $this->assertEquals($coordsArraySorted[$key], $coords);
        }
    }

    public function testContains()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsCollection = new CoordsCollection($coordsArray);
        $coordsToBeFound = 'B2';
        $coordsNotToBeFound = 'B3';

        // null always returns false
        $this->assertFalse($coordsCollection->contains(null));
        // real coords
        $this->assertTrue($coordsCollection->contains($coordsToBeFound));
        $this->assertFalse($coordsCollection->contains($coordsNotToBeFound));
    }

    public function testAppend()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsCollection = new CoordsCollection($coordsArray);
        $appendedCoord = 'H9';

        $coordsCollection->append($appendedCoord);
        $coordsArray[] = $appendedCoord;

        foreach ($coordsCollection as $key => $coords) {
            $this->assertEquals($coordsArray[$key], $coords);
        }
    }

    public function testCount()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsCollection = new CoordsCollection($coordsArray);

        $this->assertEquals(count($coordsArray), $coordsCollection->count());
    }

    public function testGetIterator()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsCollection = new CoordsCollection($coordsArray);

        $this->assertInstanceOf('\ArrayIterator', $coordsCollection->getIterator());
        $this->assertEquals(count($coordsArray), $coordsCollection->getIterator()->count());
    }
}
