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
 * @AppAssert\IsAllowedToStart()
 * @AppAssert\EventValue()
 *
 * @Serializer\ExclusionPolicy("none")
 */
class Event
{
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
     * @Assert\Choice(callback = "getTypes")
     * @AppAssert\UniqueEvent({Event::TYPE_JOIN_GAME, Event::TYPE_START_GAME, Event::TYPE_NEW_GAME})
     */
    private $type;

    /**
     * @var mixed
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
     * Set type
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
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set value
     *
     * @param mixed $value
     * @return Event
     */
    public function setValue($value)
    {
        if ($this->getType() === self::TYPE_SHOT && is_array($value)) {
            $value = array_map('trim', $value);
            $value = implode('|', $value);
        } elseif (is_string($value)) {
            $value = trim($value);
        }
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->getType() !== self::TYPE_SHOT ? $this->value : explode('|', $this->value)[0];
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

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [self::TYPE_CHAT, self::TYPE_SHOT, self::TYPE_JOIN_GAME, self::TYPE_START_GAME, self::TYPE_NAME_UPDATE, self::TYPE_NEW_GAME];
    }
}
