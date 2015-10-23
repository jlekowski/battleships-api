<?php

namespace AppBundle\Exception;

use FOS\RestBundle\Util\Codes;

class GameFlowException extends InvalidArgument400Exception
{
    protected $code = 170;

    /**
     * @var int
     */
    protected $statusCode = Codes::HTTP_CONFLICT;
}
