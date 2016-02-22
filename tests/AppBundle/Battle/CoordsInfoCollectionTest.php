<?php

namespace Tests\AppBundle\Battle;

use AppBundle\Battle\CoordsInfo;
use AppBundle\Battle\CoordsInfoCollection;

class CoordsInfoCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \AppBundle\Exception\InvalidCoordinatesException
     * @expectedExceptionMessage Invalid coordinates provided: A11
     */
    public function testConstructorThrowsExceptionWhenAnyCoordsInvalid()
    {
        new CoordsInfoCollection(['A1', 'B2', 'J10', 'A11']);
    }

    public function testClassIsTraversable()
    {
        $coordsArray = ['A1', 'B2', 'J10', 'A10'];
        $coordsInfoCollection = new CoordsInfoCollection($coordsArray);

        /** @var CoordsInfo $coordsInfo */
        foreach ($coordsInfoCollection as $key => $coordsInfo) {
            $this->assertInstanceOf('\AppBundle\Battle\CoordsInfo', $coordsInfo);
            $this->assertEquals($coordsArray[$key], $coordsInfo->getCoords());
        }
    }

    public function testSort()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsInfoCollection = new CoordsInfoCollection($coordsArray);
        $coordsInfoCollection->sort();

        $coordsArraySorted = ['A1', 'A10', 'B2', 'J1', 'J10'];
        /** @var CoordsInfo $coordsInfo */
        foreach ($coordsInfoCollection as $key => $coordsInfo) {
            $this->assertEquals($coordsArraySorted[$key], $coordsInfo->getCoords());
        }
    }

    public function testContains()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsInfoCollection = new CoordsInfoCollection($coordsArray);
        $coordsInfoToBeFound = new CoordsInfo('B2');
        $coordsInfoNotToBeFound = new CoordsInfo('B3');

        // null always returns false
        $this->assertFalse($coordsInfoCollection->contains());
        $this->assertFalse($coordsInfoCollection->contains(null));
        // real coords
        $this->assertTrue($coordsInfoCollection->contains($coordsInfoToBeFound));
        $this->assertFalse($coordsInfoCollection->contains($coordsInfoNotToBeFound));
    }

    public function testAppend()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsInfoCollection = new CoordsInfoCollection($coordsArray);
        $appendedCoord = 'H9';

        $coordsInfoCollection->append(new CoordsInfo($appendedCoord));
        $coordsArray[] = $appendedCoord;

        /** @var CoordsInfo $coordsInfo */
        foreach ($coordsInfoCollection as $key => $coordsInfo) {
            $this->assertEquals($coordsArray[$key], $coordsInfo->getCoords());
        }
    }

    public function testCount()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsInfoCollection = new CoordsInfoCollection($coordsArray);

        $this->assertEquals(count($coordsArray), $coordsInfoCollection->count());
    }

    public function testGetIterator()
    {
        $coordsArray = ['A1', 'J10', 'J1', 'B2', 'A10'];
        $coordsInfoCollection = new CoordsInfoCollection($coordsArray);

        $this->assertInstanceOf('\ArrayIterator', $coordsInfoCollection->getIterator());
        $this->assertEquals(count($coordsArray), $coordsInfoCollection->getIterator()->count());
    }
}
