<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class InvalidArgument400Exception extends \InvalidArgumentException implements HttpExceptionInterface
{
    /**
     * @var int
     */
    protected $statusCode = Response::HTTP_BAD_REQUEST;

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
