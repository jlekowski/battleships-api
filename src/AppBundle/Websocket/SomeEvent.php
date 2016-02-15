<?php

namespace AppBundle\Websocket;

use Doctrine\Common\EventArgs;

class SomeEvent extends EventArgs
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
     * @return string
     */
    public function __toString()
    {
        return sprintf('my id: %s', $this->id);
    }
}
