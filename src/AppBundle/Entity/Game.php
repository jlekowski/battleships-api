<?php

namespace AppBundle\Entity;

use AppBundle\Exception\UserNotFoundException;
use AppBundle\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GameRepository")
 * @ORM\HasLifecycleCallbacks()
 *
 * @AppAssert\OnlyBeforeStart(groups={"update"})
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Game
{
    // in seconds
    const JOIN_LIMIT = 300;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user1_id", referencedColumnName="id", nullable=false)
     */
    private $user1;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user2_id", referencedColumnName="id")
     */
    private $user2;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Event", mappedBy="game")
     */
    private $events;

    /**
     * @var array
     *
     * @ORM\Column(name="user1_ships", type="simple_array", nullable=true)
     */
    private $user1Ships = [];

    /**
     * @var array
     *
     * @ORM\Column(name="user2_ships", type="simple_array", nullable=true)
     */
    private $user2Ships = [];

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime")
     * @Serializer\Expose
     */
    private $timestamp;

    /**
     * @var int|null
     */
    private $playerNumber;

    /**
     * @var User
     */
    private $loggedUser;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

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
     * @return User
     */
    public function getUser1()
    {
        return $this->user1;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser1(User $user)
    {
        $this->user1 = $user;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser2()
    {
        return $this->user2;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser2(User $user)
    {
        $this->user2 = $user;

        return $this;
    }

    /**
     * @return ArrayCollection|Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return array
     */
    public function getUser1Ships()
    {
        return $this->user1Ships;
    }

    /**
     * @param array $user1Ships
     * @return Game
     */
    public function setUser1Ships(array $user1Ships)
    {
        $this->user1Ships = $user1Ships;

        return $this;
    }

    /**
     * @return array
     */
    public function getUser2Ships()
    {
        return $this->user2Ships;
    }

    /**
     * @param array $user2Ships
     * @return Game
     */
    public function setUser2Ships(array $user2Ships)
    {
        $this->user2Ships = $user2Ships;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     * @return Game
     */
    public function setTimestamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
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
     * @Serializer\VirtualProperty
     *
     * @return User
     */
    public function getPlayer()
    {
        return $this->getPlayerNumber() === 2 ? $this->getUser2() : $this->getUser1();
    }

    /**
     * @Serializer\VirtualProperty
     *
     * @return User
     */
    public function getOther()
    {
        return $this->getPlayerNumber() === 2 ? $this->getUser1() : $this->getUser2();
    }

    /**
     * @Serializer\VirtualProperty
     * @AppAssert\Ships(groups={"Default", "update"})
     *
     * @return array
     */
    public function getPlayerShips()
    {
        return $this->getPlayerNumber() === 2 ? $this->getUser2Ships() : $this->getUser1Ships();
    }

    /**
     * @param array $playerShips
     * @return Game
     */
    public function setPlayerShips(array $playerShips)
    {
        return $this->getPlayerNumber() === 2 ? $this->setUser2Ships($playerShips) : $this->setUser1Ships($playerShips);
    }

    /**
     * @AppAssert\Ships()
     *
     * @return array
     */
    public function getOtherShips()
    {
        return $this->getPlayerNumber() === 2 ? $this->getUser1Ships() : $this->getUser2Ships();
    }

    /**
     * @Serializer\VirtualProperty
     *
     * @return int
     * @throws \RuntimeException
     */
    public function getPlayerNumber()
    {
        if ($this->playerNumber === null) {
            $this->playerNumber = $this->findPlayerNumber();
        }

        return $this->playerNumber;
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    protected function findPlayerNumber()
    {
        if (!$this->loggedUser) {
            throw new UserNotFoundException(sprintf('Logged user has not been set for the game `%s`', $this->getId()));
        }

        $loggedUserId = $this->loggedUser->getId();
        $isUser1 = $loggedUserId === $this->user1->getId();
        if (!$isUser1 && $this->user2 && ($loggedUserId !== $this->user2->getId())) {
            throw new \RuntimeException('Game belongs to other users');
        }

        return $isUser1 ? 1 : 2;
    }

    /**
     * @return int
     */
    public function getOtherNumber()
    {
        return $this->getPlayerNumber() === 2 ? 1 : 2;
    }

    /**
     * @param User $user
     * @return Game
     */
    public function setLoggedUser(User $user)
    {
        $this->loggedUser = $user;
        $this->playerNumber = null;

        return $this;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function belongsToUser(User $user)
    {
        $gameUsers = [$this->user1->getId()];
        if ($this->user2) {
            $gameUsers[] = $this->user2->getId();
        }

        return in_array($user->getId(), $gameUsers, true);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canJoin(User $user)
    {
        return $this->isAvailable() && ($this->user2 === null) && ($this->user1->getId() !== $user->getId());
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getTimestamp() >= new \DateTime(sprintf('-%d seconds', self::JOIN_LIMIT));
    }
}
