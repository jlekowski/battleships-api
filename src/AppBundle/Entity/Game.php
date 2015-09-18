<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Game
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\GameRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @Serializer\ExclusionPolicy("none")
 */
class Game
{
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
     * @var string
     *
     * @ORM\Column(name="player1_hash", type="string", length=255)
     */
    private $player1Hash;

    /**
     * @var string
     *
     * @ORM\Column(name="player1_name", type="string", length=255)
     */
    private $player1Name;

    /**
     * @var array
     *
     * @ORM\Column(name="player1_ships", type="simple_array", nullable = true)
     */
    private $player1Ships;

    /**
     * @var string
     *
     * @ORM\Column(name="player2_hash", type="string", length=255)
     */
    private $player2Hash;

    /**
     * @var string
     *
     * @ORM\Column(name="player2_name", type="string", length=255)
     */
    private $player2Name;

    /**
     * @var array
     *
     * @ORM\Column(name="player2_ships", type="simple_array", nullable = true)
     */
    private $player2Ships;

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
     * Set player1Hash
     *
     * @param string $player1Hash
     * @return Game
     */
    public function setPlayer1Hash($player1Hash)
    {
        $this->player1Hash = $player1Hash;

        return $this;
    }

    /**
     * Get player1Hash
     *
     * @return string
     */
    public function getPlayer1Hash()
    {
        return $this->player1Hash;
    }

    /**
     * Set player1Name
     *
     * @param string $player1Name
     * @return Game
     */
    public function setPlayer1Name($player1Name)
    {
        $this->player1Name = $player1Name;

        return $this;
    }

    /**
     * Get player1Name
     *
     * @return string
     */
    public function getPlayer1Name()
    {
        return $this->player1Name;
    }

    /**
     * Set player1Ships
     *
     * @param array $player1Ships
     * @return Game
     */
    public function setPlayer1Ships(array $player1Ships)
    {
        $this->player1Ships = $player1Ships;

        return $this;
    }

    /**
     * Get player1Ships
     *
     * @return array
     */
    public function getPlayer1Ships()
    {
        return $this->player1Ships;
    }

    /**
     * Set player2Hash
     *
     * @param string $player2Hash
     * @return Game
     */
    public function setPlayer2Hash($player2Hash)
    {
        $this->player2Hash = $player2Hash;

        return $this;
    }

    /**
     * Get player2Hash
     *
     * @return string
     */
    public function getPlayer2Hash()
    {
        return $this->player2Hash;
    }

    /**
     * Set player2Name
     *
     * @param string $player2Name
     * @return Game
     */
    public function setPlayer2Name($player2Name)
    {
        $this->player2Name = $player2Name;

        return $this;
    }

    /**
     * Get player2Name
     *
     * @return string
     */
    public function getPlayer2Name()
    {
        return $this->player2Name;
    }

    /**
     * Set player2Ships
     *
     * @param array $player2Ships
     * @return Game
     */
    public function setPlayer2Ships(array $player2Ships)
    {
        $this->player2Ships = $player2Ships;

        return $this;
    }

    /**
     * Get player2Ships
     *
     * @return array
     */
    public function getPlayer2Ships()
    {
        return $this->player2Ships;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return Game
     */
    public function setTimestamp(\DateTime $timestamp)
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
        $this->setTimestamp(new \DateTime());
    }
}
