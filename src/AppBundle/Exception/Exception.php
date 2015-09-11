<?php

namespace AppBundle\Exception;

class Exception extends \Exception
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
//        echo "<pre>EXCEPTION CLASS\n";
//        var_dump($previous);
//        exit;
        parent::__construct($message, $code, $previous);
    }
}
