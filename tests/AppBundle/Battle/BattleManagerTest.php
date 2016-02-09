<?php

namespace AppBundle\Tests\Battle;

use AppBundle\Battle\BattleManager;
use AppBundle\Battle\CoordsInfo;
use AppBundle\Battle\CoordsInfoCollection;

class BattleManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BattleManager
     */
    protected $battleManager;

    public function setUp()
    {
        $eventRepository = $this->prophesize('AppBundle\Entity\EventRepository');
        $this->battleManager = new BattleManager($eventRepository->reveal());
    }

    public function testIsSunk()
    {
        $mast = new CoordsInfo('B2');
        $allShips = new CoordsInfoCollection(['B1', 'B2', 'B3']);
        $allShots = new CoordsInfoCollection(['B1', 'B3']);

        $this->assertTrue($this->battleManager->isSunk($mast, $allShips, $allShots));

        $mast = new CoordsInfo('B3');
        $allShips = new CoordsInfoCollection(['B1', 'B2', 'B3', 'B4']);
        $allShots = new CoordsInfoCollection(['B1', 'B2', 'B4']);

        $this->assertTrue($this->battleManager->isSunk($mast, $allShips, $allShots));

        $mast = new CoordsInfo('B3');
        $allShips = new CoordsInfoCollection(['B3']);
        $allShots = new CoordsInfoCollection([]);

        $this->assertTrue($this->battleManager->isSunk($mast, $allShips, $allShots));

        $mast = new CoordsInfo('B3');
        $allShips = new CoordsInfoCollection(['B1', 'B2', 'B3', 'B4']);
        $allShots = new CoordsInfoCollection(['B1', 'B4']);

        $this->assertFalse($this->battleManager->isSunk($mast, $allShips, $allShots));
    }
}
