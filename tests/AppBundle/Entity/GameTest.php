<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Game;
use Doctrine\Common\Collections\ArrayCollection;

class GameTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Game
     */
    protected $game;

    public function setUp()
    {
        $this->game = new Game();
    }

    public function testGetPlayerAndOther()
    {
        $user1 = $this->prophesize('AppBundle\Entity\User');
        $user2 = $this->prophesize('AppBundle\Entity\User');

        $user1->getId()->willReturn(1);
        $user2->getId()->willReturn(2);
        $this->game->setUser1($user1->reveal());
        $this->game->setUser2($user2->reveal());

        $this->game->setLoggedUser($user1->reveal());
        $this->assertEquals($user1->reveal(), $this->game->getPlayer());
        $this->assertEquals($user2->reveal(), $this->game->getOther());

        $this->game->setLoggedUser($user2->reveal());
        $this->assertEquals($user2->reveal(), $this->game->getPlayer());
        $this->assertEquals($user1->reveal(), $this->game->getOther());
    }

    public function testGetPlayerNumberAndOtherNumber()
    {
        $user1 = $this->prophesize('AppBundle\Entity\User');
        $user2 = $this->prophesize('AppBundle\Entity\User');

        $user1->getId()->willReturn(1);
        $user2->getId()->willReturn(2);

        // no user2, logged user1
        $this->game->setUser1($user1->reveal());
        $this->game->setLoggedUser($user1->reveal());
        $this->assertEquals(1, $this->game->getPlayerNumber());
        $this->assertEquals(2, $this->game->getOtherNumber());

        // no user2, logged user2
        $this->game->setLoggedUser($user2->reveal());
        $this->assertEquals(2, $this->game->getPlayerNumber());
        $this->assertEquals(1, $this->game->getOtherNumber());

        // user2, logged user2
        $this->game->setUser2($user2->reveal());
        $this->assertEquals(2, $this->game->getPlayerNumber());
        $this->assertEquals(1, $this->game->getOtherNumber());

        // user2, logged user1
        $this->game->setLoggedUser($user1->reveal());
        $this->assertEquals(1, $this->game->getPlayerNumber());
        $this->assertEquals(2, $this->game->getOtherNumber());
    }

    /**
     * @expectedException \AppBundle\Exception\UserNotFoundException
     * @expectedExceptionMessage Logged user has not been set for the game ``
     */
    public function testGetPlayerNumberThrowsExceptionWhenLoggedUserMissing()
    {
        $this->game->getPlayerNumber();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Game belongs to other users
     */
    public function testGetPlayerNumberThrowsExceptionWhenGameDoesNotBelongToLoggedUser()
    {
        $user1 = $this->prophesize('AppBundle\Entity\User');
        $user2 = $this->prophesize('AppBundle\Entity\User');
        $user3 = $this->prophesize('AppBundle\Entity\User');

        $user1->getId()->willReturn(1);
        $user2->getId()->willReturn(2);
        $user3->getId()->willReturn(3);

        $this->game->setUser1($user1->reveal());
        $this->game->setUser2($user2->reveal());
        $this->game->setLoggedUser($user3->reveal());

        $this->game->getPlayerNumber();
    }

    public function testBelongsToUser()
    {
        $user1 = $this->prophesize('AppBundle\Entity\User');
        $user2 = $this->prophesize('AppBundle\Entity\User');
        $user3 = $this->prophesize('AppBundle\Entity\User');

        $user1->getId()->willReturn(1);
        $user2->getId()->willReturn(2);
        $user3->getId()->willReturn(3);

        $this->game->setUser1($user1->reveal());
        $this->game->setUser2($user2->reveal());

        $this->assertTrue($this->game->belongsToUser($user1->reveal()));
        $this->assertTrue($this->game->belongsToUser($user2->reveal()));
        $this->assertFalse($this->game->belongsToUser($user3->reveal()));
    }

    public function testSetUserSetsUserId()
    {
        $user1 = $this->prophesize('AppBundle\Entity\User');
        $user2 = $this->prophesize('AppBundle\Entity\User');

        $user1->getId()->willReturn(1);
        $user2->getId()->willReturn(2);

        $this->game->setUser1($user1->reveal());
        $this->game->setUser2($user2->reveal());
        $this->assertEquals($user1->reveal(), $this->game->getUser1());
        $this->assertEquals($user2->reveal(), $this->game->getUser2());
        $this->assertEquals(1, $this->game->getUserId1());
        $this->assertEquals(2, $this->game->getUserId2());

        $this->game->setUser1($user2->reveal());
        $this->game->setUser2($user1->reveal());
        $this->assertEquals($user2->reveal(), $this->game->getUser1());
        $this->assertEquals($user1->reveal(), $this->game->getUser2());
        $this->assertEquals(2, $this->game->getUserId1());
        $this->assertEquals(1, $this->game->getUserId2());
    }

    public function testApplyCurrentTimestamp()
    {
        $this->assertNull($this->game->getTimestamp());

        $this->game->applyCurrentTimestamp();

        $this->assertInstanceOf(\DateTime::class, $this->game->getTimestamp());
        $timeDiff = time() - $this->game->getTimestamp()->getTimestamp();
        $this->assertTrue($timeDiff < 1 && $timeDiff >=0);

        $now = new \DateTime();
        $this->game->setTimestamp($now);
        $this->assertEquals($now, $this->game->getTimestamp());
    }

    public function testCanJoin()
    {
        $user1 = $this->prophesize('AppBundle\Entity\User');
        $user2 = $this->prophesize('AppBundle\Entity\User');
        $user3 = $this->prophesize('AppBundle\Entity\User');

        $user1->getId()->willReturn(1);
        $user2->getId()->willReturn(2);
        $user3->getId()->willReturn(3);

        // game not available
        $this->game->setTimestamp(new \DateTime(sprintf('-%d seconds', Game::JOIN_LIMIT + 1)));
        $this->assertFalse($this->game->canJoin($user3->reveal()));

        $this->game->setUser1($user1->reveal());
        // user already owns the game
        $this->game->setTimestamp(new \DateTime());
        $this->assertFalse($this->game->canJoin($user1->reveal()));

        // user can join
        $this->game->setTimestamp(new \DateTime());
        $this->assertTrue($this->game->canJoin($user2->reveal()));

        // both users set
        $this->game->setUser2($user2->reveal());
        $this->game->setTimestamp(new \DateTime());
        $this->assertFalse($this->game->canJoin($user3->reveal()));
    }

    public function testPlayerShipsAndOtherShips()
    {
        $user1 = $this->prophesize('AppBundle\Entity\User');
        $user2 = $this->prophesize('AppBundle\Entity\User');
        $user1Ships = ['A1'];
        $user2Ships = ['B2'];

        $user1->getId()->willReturn(1);
        $user2->getId()->willReturn(2);
        $this->game->setUser1Ships($user1Ships);
        $this->game->setUser2Ships($user2Ships);
        $this->game->setUser1($user1->reveal());
        $this->game->setUser2($user2->reveal());

        $this->game->setLoggedUser($user1->reveal());
        $this->assertEquals($user1Ships, $this->game->getPlayerShips());
        $this->assertEquals($user2Ships, $this->game->getOtherShips());

        $this->game->setLoggedUser($user2->reveal());
        $this->assertEquals($user2Ships, $this->game->getPlayerShips());
        $this->assertEquals($user1Ships, $this->game->getOtherShips());
    }

    public function testGetEvents()
    {
        $events = $this->game->getEvents();
        $this->assertInstanceOf(ArrayCollection::class, $events);
        $this->assertCount(0, $events);
    }
}
