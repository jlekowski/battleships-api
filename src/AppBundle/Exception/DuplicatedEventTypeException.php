<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class DuplicatedEventTypeException extends InvalidArgument400Exception
{
    protected $code = 190;

    /**
     * @var int
     */
    protected $statusCode = Response::HTTP_FORBIDDEN;

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
