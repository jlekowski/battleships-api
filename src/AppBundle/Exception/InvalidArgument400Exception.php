<?php

namespace AppBundle\Exception;

use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class InvalidArgument400Exception extends \InvalidArgumentException implements HttpExceptionInterface
{
    /**
     * @var int
     */
    protected $statusCode = Codes::HTTP_BAD_REQUEST;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @inheritdoc
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
