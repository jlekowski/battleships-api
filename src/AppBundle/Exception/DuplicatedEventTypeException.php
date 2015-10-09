<?php

namespace AppBundle\Exception;

class DuplicatedEventTypeException extends InvalidEventTypeException
{
    protected $code = 172;

    /**
     * @param string $eventType
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($eventType = '', $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Event `%s` has already been created', $eventType);

        parent::__construct($message, $code, $previous);
    }
}
