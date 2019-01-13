<?php

namespace AppBundle\EventListener;

use AppBundle\Http\Headers;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CorsListener
{
    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->getResponse()->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Accept, X-Requested-With',
            'Access-Control-Max-Age' => 86400,
            'Access-Control-Expose-Headers' => sprintf('Location, %s', Headers::API_KEY)
        ]);
    }
}
