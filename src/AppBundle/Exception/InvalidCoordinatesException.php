<?php

namespace AppBundle\Exception;

class InvalidCoordinatesException extends InvalidShipsException
{
    protected $code = 151;

    /**
     * @param string $coords
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($coords = '', $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Invalid coordinates provided: %s', $coords);

        parent::__construct($message, $code, $previous);
    }
}
