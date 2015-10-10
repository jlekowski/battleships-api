<?php

namespace AppBundle\Exception;

class UnexpectedEventTypeException extends InvalidEventTypeException
{
    protected $code = 181;

    /**
     * @param string $eventTypeReceived
     * @param string $eventTypeExcepted
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(
        $eventTypeReceived = '',
        $eventTypeExcepted = '',
        $code = 0,
        \Exception $previous = null
    ) {
        $message = sprintf(
            'Incorrect event type provided: %s (expected: %s)',
            $eventTypeReceived,
            $eventTypeExcepted ?: 'other'
        );

        parent::__construct($message, $code, $previous);
    }
}
