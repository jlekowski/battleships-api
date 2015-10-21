<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Options;

class CorsController
{
    /**
     * jQuery CORS required http://www.html5rocks.com/en/tutorials/cors/
     * @Options("/v{version}/games", name="_games", requirements={"version" = "1"})
     * @Options("/v{version}/games/{game}", name="_game", requirements={"version" = "1"})
     * @Options("/v{version}/games/{game}/events", name="_game_events", requirements={"version" = "1"})
     * @Options("/v{version}/games/{game}/events/{event}", name="_game_event", requirements={"version" = "1"})
     */
    public function optionsAction()
    {
    }
}
