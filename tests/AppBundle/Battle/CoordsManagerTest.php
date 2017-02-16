<?php

namespace Tests\AppBundle\Battle;

use AppBundle\Battle\CoordsManager;

class CoordsManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $offsets = [
        CoordsManager::OFFSET_TOP,
        CoordsManager::OFFSET_BOTTOM,
        CoordsManager::OFFSET_RIGHT,
        CoordsManager::OFFSET_LEFT,
        CoordsManager::OFFSET_TOP_RIGHT,
        CoordsManager::OFFSET_TOP_LEFT,
        CoordsManager::OFFSET_BOTTOM_RIGHT,
        CoordsManager::OFFSET_BOTTOM_LEFT
    ];

    /**
     * @var CoordsManager
     */
    protected $coordsManager;

    public function setUp()
    {
        $this->coordsManager = new CoordsManager();
    }

    public function testGetByOffset()
    {
        $coords = 'B2';
        $expectedOffsetCoords = ['A2', 'C2', 'B3', 'B1', 'A3', 'A1', 'C3', 'C1'];
        $expectedResults = array_combine($this->offsets, $expectedOffsetCoords);

        foreach ($expectedResults as $offset => $expectedCoords) {
            $this->assertEquals($expectedCoords, $this->coordsManager->getByOffset($coords, $offset));
        }
    }

    public function testGetByOffsetOutOfBoard()
    {
        $coordsForOffsets = ['A6', 'J2', 'B10', 'H1', 'A10', 'A1', 'J10', 'J1'];
        $data = array_combine($this->offsets, $coordsForOffsets);

        foreach ($data as $offset => $coords) {
            $this->assertNull($this->coordsManager->getByOffset($coords, $offset));
        }
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidCoordinatesException
     * @expectedExceptionMessage Invalid coordinates provided
     * @dataProvider incorrectCoordsProvider
     * @param string $coords
     */
    public function testGetByOffsetIncorrectCoord($coords)
    {
        $this->coordsManager->getByOffset($coords, CoordsManager::OFFSET_RIGHT);
    }

    public function testGetByOffsets()
    {
        $expectedOffsetCoords = ['H9', 'J9', 'I10', 'I8', 'H10', 'H8', 'J10', 'J8'];
        $expectedResult = array_combine($this->offsets, $expectedOffsetCoords);
        $coords = 'I9';

        $this->assertEquals($expectedResult, $this->coordsManager->getByOffsets($coords, $this->offsets));
    }

    public function testValidateCoordsCorrect()
    {
        $coordsArray = ['A2', 'C2', 'B3', 'B1', 'A3', 'A1', 'C3', 'C1', 'H9', 'J9', 'I10', 'I8', 'H10', 'H8', 'J10', 'J8'];

        foreach ($coordsArray as $coords) {
            $this->coordsManager->validateCoords($coords);
        }
    }

    public function testValidateCoordsArrayCorrect()
    {
        $coordsArray = ['A2', 'C2', 'B3', 'B1', 'A3', 'A1', 'C3', 'C1', 'H9', 'J9', 'I10', 'I8', 'H10', 'H8', 'J10', 'J8'];

        $this->coordsManager->validateCoordsArray($coordsArray);
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidCoordinatesException
     * @expectedExceptionMessage Invalid coordinates provided
     * @dataProvider incorrectCoordsProvider
     * @param string $coords
     */
    public function testValidateCoordsIncorrect($coords)
    {
        $this->coordsManager->validateCoords($coords);
    }
    /**
     * @expectedException \AppBundle\Exception\InvalidCoordinatesException
     * @expectedExceptionMessage Invalid coordinates provided
     * @dataProvider incorrectCoordsArrayProvider
     * @param array $coords
     */
    public function testValidateCoordsArrayIncorrect(array $coords)
    {
        $this->coordsManager->validateCoordsArray($coords);
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidOffsetException
     * @expectedExceptionMessage Invalid offset provided: invalidOffset
     */
    public function testGetByOffsetThrowsExceptionOnIncorrectOffset()
    {
        $this->coordsManager->getByOffset('coords', 'invalidOffset');
    }

    public function incorrectCoordsProvider()
    {
        return [['M22'], [''], ['123'], ['1'], [1], [0], [null], ['A11'], ['J11'], ['N5'], ['H0'], ['a1'], ['h5']];
    }

    public function incorrectCoordsArrayProvider()
    {
        return [[['A1', null, 'B2']], [['J1', 'G5', 'H0']], [['N5', 'F4', 'E5']], [[1]], [['j10']]];
    }
}
