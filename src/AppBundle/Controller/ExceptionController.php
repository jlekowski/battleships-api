<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\ExceptionController as FosExceptionController;
use Symfony\Component\HttpFoundation\Request;

class ExceptionController extends FosExceptionController
{
    /**
     * @inheritdoc
     */
    protected function createView(\Exception $exception, $code, array $templateData, Request $request, $showException)
    {
        $templateData['status_code'] = $exception->getCode() ?: $templateData['status_code'];

        return parent::createView($exception, $code, $templateData, $request, $showException);
    }
}
