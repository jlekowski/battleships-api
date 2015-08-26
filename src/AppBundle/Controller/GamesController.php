<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GamesController extends Controller
{
    public function __construct()
    {

    }

    public function indexAction($name)
    {
        return $this->render('', array('name' => $name));
    }
}
