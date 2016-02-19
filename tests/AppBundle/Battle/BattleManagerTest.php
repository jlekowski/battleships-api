<?php

namespace AppBundle\Tests\Battle;

use AppBundle\Battle\BattleManager;
use AppBundle\Battle\CoordsInfo;
use AppBundle\Battle\CoordsInfoCollection;
use AppBundle\Entity\Event;
use Prophecy\Prophecy\ObjectProphecy;

class BattleManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BattleManager
     */
    protected $battleManager;

    /**
     * @var ObjectProphecy
     */
    protected $eventRepository;

    public function setUp()
    {
        $this->eventRepository = $this->prophesize('AppBundle\Entity\EventRepository');
        $this->battleManager = new BattleManager($this->eventRepository->reveal());
    }

    public function testGetShotResultMiss()
    {
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);
        $event->getValue()->willReturn('A10');

        $game->getOtherShips()->willReturn(['A1']);

        $this->assertEquals(BattleManager::SHOT_RESULT_MISS, $this->battleManager->getShotResult($event->reveal()));
    }

    public function testGetShotResultHit()
    {
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);
        $event->getValue()->willReturn('C2');
        $event->getPlayer()->willReturn(1);

        $game->getOtherShips()->willReturn(['C2','D2']);

        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_SHOT, 1)->willReturn([]);

        $this->assertEquals(BattleManager::SHOT_RESULT_HIT, $this->battleManager->getShotResult($event->reveal()));
    }

    public function testGetShotResultSunk()
    {
        $shotEvent = $this->prophesize('AppBundle\Entity\Event');
        $event = $this->prophesize('AppBundle\Entity\Event');
        $game = $this->prophesize('AppBundle\Entity\Game');

        $shotEvent->getValue()->willReturn('D2|hit');

        $event->getType()->willReturn(Event::TYPE_SHOT);
        $event->getGame()->willReturn($game);
        $event->getValue()->willReturn('C2');
        $event->getPlayer()->willReturn(1);

        $game->getOtherShips()->willReturn(['C2','D2']);

        $attackerShots = [$shotEvent];
        $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_SHOT, 1)->willReturn($attackerShots);

        $this->assertEquals(BattleManager::SHOT_RESULT_SUNK, $this->battleManager->getShotResult($event->reveal()));
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
