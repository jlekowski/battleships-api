<?php

namespace AppBundle\Entity;

class User
{
    /**
     * @var string
     */
    protected $playerHash;

    /**
     * @param int $playerHash
     */
    public function __construct($playerHash)
    {
        $this->playerHash = $playerHash;
    }

    /**
     * @return string
     */
    public function getPlayerHash()
    {
        return $this->playerHash;
    }

    /**
     * @todo do I really need this?
     * @return array
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * @todo something better than this
     * @return string
     */
    public function __toString()
    {
        return 'game user';
    }
}
