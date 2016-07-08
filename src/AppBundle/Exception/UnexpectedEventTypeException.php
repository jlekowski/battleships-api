<?php

namespace AppBundle\Exception;

class UnexpectedEventTypeException extends \InvalidArgumentException
{
    protected $code = 180;

    /**
     * @param string $eventTypeReceived
     * @param string $eventTypeExpected
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($eventTypeReceived, $eventTypeExpected, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Incorrect event type provided: %s (expected: %s)', $eventTypeReceived, $eventTypeExpected);

        parent::__construct($message, $code, $previous);
    }
}
