<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class GameFlowException extends InvalidArgument400Exception
{
    protected $code = 170;

    /**
     * @var int
     */
    protected $statusCode = Response::HTTP_CONFLICT;
}
