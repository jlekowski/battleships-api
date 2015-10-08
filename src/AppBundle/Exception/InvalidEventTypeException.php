<?php

namespace AppBundle\Exception;

class InvalidEventTypeException extends \InvalidArgumentException
{
    protected $code = 170;

    /**
     * @param string $eventType
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($eventType = '', $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Invalid event type provided: %s', $eventType);

        parent::__construct($message, $code, $previous);
    }
}
