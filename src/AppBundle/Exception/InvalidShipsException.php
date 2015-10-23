<?php

namespace AppBundle\Exception;

class InvalidShipsException extends InvalidArgument400Exception
{
    protected $code = 150;
}
