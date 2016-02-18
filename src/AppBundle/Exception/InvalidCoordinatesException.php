<?php

namespace AppBundle\Exception;

class InvalidCoordinatesException extends InvalidArgument400Exception
{
    protected $code = 160;

    /**
     * @param string $coords
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($coords = '', $code = 0, \Exception $previous = null)
    {
        // just in case to prevent array to string conversion warning
        $message = sprintf('Invalid coordinates provided: %s', is_array($coords) ? 'array()' : $coords);

        parent::__construct($message, $code, $previous);
    }
}
