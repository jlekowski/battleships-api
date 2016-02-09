<?php

namespace BattleshipsApiComponent\Client\Response;

class ApiResponse
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * @param string $body
     * @param array $headers
     */
    public function __construct($body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $header
     * @return string|null
     */
    public function getHeader($header)
    {
        return isset($this->headers[$header]) ? $this->headers[$header] : null;
    }

    /**
     * @return \stdClass|mixed
     */
    public function getJson()
    {
        return json_decode($this->body);
    }
}
