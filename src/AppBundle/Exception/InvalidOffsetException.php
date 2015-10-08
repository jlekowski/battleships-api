<?php

namespace AppBundle\Exception;

class InvalidOffsetException extends InvalidCoordinatesException
{
    protected $code = 162;

    /**
     * @param string $offset
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($offset = '', $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Invalid offset provided: %s', $offset);

        parent::__construct($message, $code, $previous);
    }
}
