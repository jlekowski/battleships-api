<?php

namespace AppBundle\Websocket;

use Symfony\Component\EventDispatcher\Event;

class SomeEvent extends Event
{
    /**
     * @var int
     */
    private $id;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf('my id: %d', $this->id);
    }
}
