<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Event
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EventRepository")
 * @ORM\HasLifecycleCallbacks()
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

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Exclude
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="game_id", type="integer")
     */
    private $gameId;

    /**
     * @var integer
     *
     * @ORM\Column(name="player", type="smallint")
     */
    private $player;

    /**
     * @var string
     *
     * @ORM\Column(name="event_type", type="string", length=255)
     */
    private $eventType;

    /**
     * @var string
     *
     * @ORM\Column(name="event_value", type="text")
     */
    private $eventValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime")
     * @Serializer\Exclude
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
     * Set gameId
     *
     * @param integer $gameId
     * @return Event
     */
    public function setGameId($gameId)
    {
        $this->gameId = $gameId;

        return $this;
    }

    /**
     * Get gameId
     *
     * @return integer
     */
    public function getGameId()
    {
        return $this->gameId;
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
     * @param string $eventType
     * @return Event
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * Get eventType
     *
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * Set eventValue
     *
     * @param string $eventValue
     * @return Event
     */
    public function setEventValue($eventValue)
    {
        $this->eventValue = $eventValue;

        return $this;
    }

    /**
     * Get eventValue
     *
     * @return string
     */
    public function getEventValue()
    {
        return $this->eventValue;
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
     * @todo here and in Game apply only if not already set
     */
    public function applyCurrentTimestamp()
    {
        $this->setTimestamp(new \DateTime());
    }
}
