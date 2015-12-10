<?php

namespace AppBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidApiKeyException extends AuthenticationException
{
    protected $code = 210;

    /**
     * @param string $apiKey
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($apiKey = '', $code = 0, \Exception $previous = null)
    {
        $message = sprintf('API key `%s` is invalid', $apiKey);

        parent::__construct($message, $code, $previous);
    }
}
