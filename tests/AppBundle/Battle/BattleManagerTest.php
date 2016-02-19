<?php

namespace AppBundle\Tests\Battle;

use AppBundle\Battle\BattleManager;
use AppBundle\Battle\CoordsInfo;
use AppBundle\Battle\CoordsInfoCollection;
use AppBundle\Entity\Event;

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

    public function testGetShotResultMiss()
    {
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);
        $event->getValue()->willReturn('A10');

        $game->getOtherShips()->willReturn(['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10']);

        $this->assertEquals(BattleManager::SHOT_RESULT_MISS, $this->battleManager->getShotResult($event->reveal()));
    }

    /**
     * @expectedException \AppBundle\Exception\UnexpectedEventTypeException
     * @expectedExceptionMessage Incorrect event type provided: test (expected: shot)
     */
    public function testGetShotResultThrowsExceptionForNonShotEvent()
    {
        $event = $this->prophesize('AppBundle\Entity\Event');
        $event->getType()->willReturn('test');

        $this->battleManager->getShotResult($event->reveal());
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
