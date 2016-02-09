<?php

namespace BattleshipsApiComponent\Client\Request;

class RequestDetails
{
    private $request;
    private $method;
    private $data;
    private $expectedHttpCode;

    public function __construct($request, $method, $data = null, $expectedHttpCode = 200)
    {
        $this->request = $request;
        $this->method = strtoupper($method);
        $this->data = $data;
        $this->expectedHttpCode = $expectedHttpCode;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getData()
    {
        return json_encode($this->data);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getExpectedHttpCode()
    {
        return $this->expectedHttpCode;
    }
}
