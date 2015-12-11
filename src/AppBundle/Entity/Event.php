<?php

namespace AppBundle\Entity;

use AppBundle\Validator\Constraints as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EventRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @AppAssert\IsAllowedToShoot()
 *
 * @Serializer\ExclusionPolicy("none")
 */
class Event
{
    // @todo types are log related (name_update, start/joing as game state), and flow related (chat/shot)
    //       maybe games/{id} with /chats /shots and game (bit) status and initial get would be to get lastIdEvents? :/
    const TYPE_CHAT = 'chat';
    const TYPE_SHOT = 'shot';
    const TYPE_JOIN_GAME = 'join_game';
    const TYPE_START_GAME = 'start_game';
    const TYPE_NAME_UPDATE = 'name_update';
    const TYPE_NEW_GAME = 'new_game';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Game
     *
     * @ORM\ManyToOne(targetEntity="Game", inversedBy="events")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="id", nullable=false)
     * @Serializer\Exclude
     */
    private $game;

    /**
     * @var integer
     *
     * @ORM\Column(name="player", type="smallint")
     * @Assert\Choice({1,2})
     */
    private $player;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @Assert\Choice(
     *     {Event::TYPE_CHAT, Event::TYPE_SHOT, Event::TYPE_JOIN_GAME, Event::TYPE_START_GAME, Event::TYPE_NAME_UPDATE, Event::TYPE_NEW_GAME}
     * )
     * @AppAssert\UniqueEvent({Event::TYPE_JOIN_GAME, Event::TYPE_START_GAME, Event::TYPE_NEW_GAME})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     */
    private $value = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime")
     */
    private $timestamp;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set game
     *
     * @param Game $game
     * @return Event
     */
    public function setGame(Game $game)
    {
        $this->game = $game;

        return $this;
    }

    /**
     * Get game
     *
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * Set player
     *
     * @param integer $player
     * @return Event
     */
    public function setPlayer($player)
    {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player
     *
     * @return integer
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * Set eventType
     *
     * @param string $type
     * @return Event
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get eventType
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set eventValue
     *
     * @param string $value
     * @return Event
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get eventValue
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return Event
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @ORM\PrePersist
     */
    public function applyCurrentTimestamp()
    {
        if (!$this->getTimestamp()) {
            $this->setTimestamp(new \DateTime());
        }
    }
}
