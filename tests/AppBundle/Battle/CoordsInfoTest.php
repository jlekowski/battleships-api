<?php

namespace Tests\AppBundle\Battle;

use AppBundle\Battle\CoordsInfo;

class CoordsInfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \AppBundle\Exception\InvalidCoordinatesException
     * @expectedExceptionMessage Invalid coordinates provided:
     */
    public function testConstructorThrowsExceptionForEmptyCoords()
    {
        new CoordsInfo(null);
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidCoordinatesException
     * @expectedExceptionMessage Invalid coordinates provided: array()
     */
    public function testConstructorThrowsExceptionForEmptyArrayCoords()
    {
        new CoordsInfo([]);
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidCoordinatesException
     * @expectedExceptionMessage Invalid coordinates provided: A0
     */
    public function testConstructorThrowsExceptionForInexistingCoords()
    {
        new CoordsInfo('A0');
    }

    public function testConstructorParsesProvidedCoords()
    {
        $coordsInfo = new CoordsInfo('J10');

        $this->assertEquals($coordsInfo->getCoords(), 'J10');
        $this->assertEquals($coordsInfo->getCoordY(), 'J');
        $this->assertEquals($coordsInfo->getCoordX(), '10');
        $this->assertEquals($coordsInfo->getPositionY(), 9);
        $this->assertEquals($coordsInfo->getPositionX(), 9);
    }

    public function testConstructorParsesProvidedPosition()
    {
        $coordsInfo = new CoordsInfo([9, 9]);

        $this->assertEquals($coordsInfo->getCoords(), 'J10');
        $this->assertEquals($coordsInfo->getCoordY(), 'J');
        $this->assertEquals($coordsInfo->getCoordX(), '10');
        $this->assertEquals($coordsInfo->getPositionY(), 9);
        $this->assertEquals($coordsInfo->getPositionX(), 9);
    }

    public function testGetTopPositionWhenExists()
    {
        $coordsInfo = new CoordsInfo('B2');
        $topPosition = $coordsInfo->getTopPosition();

        $this->assertInstanceOf('\AppBundle\Battle\CoordsInfo', $topPosition);
        $this->assertEquals('A2', $topPosition->getCoords());
    }

    public function testGetBottomPositionWhenExists()
    {
        $coordsInfo = new CoordsInfo('B2');
        $bottomPosition = $coordsInfo->getBottomPosition();

        $this->assertInstanceOf('\AppBundle\Battle\CoordsInfo', $bottomPosition);
        $this->assertEquals('C2', $bottomPosition->getCoords());
    }

    public function testGetRightPositionWhenExists()
    {
        $coordsInfo = new CoordsInfo('B2');
        $rightPosition = $coordsInfo->getRightPosition();

        $this->assertInstanceOf('\AppBundle\Battle\CoordsInfo', $rightPosition);
        $this->assertEquals('B3', $rightPosition->getCoords());
    }

    public function testGetLeftPositionWhenExists()
    {
        $coordsInfo = new CoordsInfo('B2');
        $leftPosition = $coordsInfo->getLeftPosition();

        $this->assertInstanceOf('\AppBundle\Battle\CoordsInfo', $leftPosition);
        $this->assertEquals('B1', $leftPosition->getCoords());
    }

    public function testGetTopPositionWhenNotExists()
    {
        $coordsInfo = new CoordsInfo('A6');

        $this->assertNull($coordsInfo->getTopPosition());
    }

    public function testGetBottomPositionWhenNotExists()
    {
        $coordsInfo = new CoordsInfo('J9');

        $this->assertNull($coordsInfo->getBottomPosition());
    }

    public function testGetRightPositionWhenNotExists()
    {
        $coordsInfo = new CoordsInfo('G10');

        $this->assertNull($coordsInfo->getRightPosition());
    }

    public function testGetLeftPositionWhenNotExists()
    {
        $coordsInfo = new CoordsInfo('I1');

        $this->assertNull($coordsInfo->getLeftPosition());
    }

    public function testGetSidePositions()
    {
        $coordsInfo = new CoordsInfo('I9');
        $sidePositions = $coordsInfo->getSidePositions();

        $this->assertCount(4, $sidePositions);
        $coords = ['H9', 'I10', 'J9' , 'I8'];
        foreach ($sidePositions as $sidePosition) {
            $this->assertTrue(in_array($sidePosition->getCoords(), $coords, true));
        }
    }
}
