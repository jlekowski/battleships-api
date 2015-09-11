<?php

namespace AppBundle\Handler;

use Symfony\Component\Debug\Exception\FlattenException;

class ExceptionWrapperHandler
{
    /**
     * @param array $data
     * @return array
     */
    public function wrap(array $data)
    {
        /** @var FlattenException $exception */
        $exception = $data['exception'];

        return [
            'code' => $exception->getStatusCode(),
            'message' => $exception->getMessage(),
            'class' => get_class($exception) . ' cool',
            'a' => $exception->getClass()
        ];
    }
}
