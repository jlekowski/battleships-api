<?php

namespace Tests\AppBundle\Battle;

use AppBundle\Battle\CoordsManager;

class CoordsManagerTest extends \PHPUnit_Framework_TestCase
{
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
        $offsets = [
            CoordsManager::OFFSET_TOP,
            CoordsManager::OFFSET_BOTTOM,
            CoordsManager::OFFSET_RIGHT,
            CoordsManager::OFFSET_LEFT,
            CoordsManager::OFFSET_TOP_RIGHT,
            CoordsManager::OFFSET_TOP_LEFT,
            CoordsManager::OFFSET_BOTTOM_RIGHT,
            CoordsManager::OFFSET_BOTTOM_LEFT
        ];
        $expectations = ['A2', 'C2', 'B3', 'B1', 'A3', 'A1', 'C3', 'C1'];
        $coords = 'B2';

        foreach ($offsets as $key => $offset) {
            $this->assertEquals($expectations[$key], $this->coordsManager->getByOffset($coords, $offset));
        }
    }

    /**
     * @expectedException \AppBundle\Exception\InvalidOffsetException
     * @expectedExceptionMessage Invalid offset provided: invalidOffset
     */
    public function testGetByOffsetThrowsExceptionOnIncorrectOffset()
    {
        $this->coordsManager->getByOffset('coords', 'invalidOffset');
    }
}
