<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Game;

class GameTest extends \PHPUnit_Framework_TestCase
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
}
