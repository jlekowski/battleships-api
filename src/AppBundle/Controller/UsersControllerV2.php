<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UsersControllerV2 extends Controller
{
    /**
     * @return array
     */
    public function getUsersAction()
    {
//        throw new \Exception('test exception');
        return ['User' => ['Jan', 'Test']];
    }

    /**
     * @return array
     */
    public function getUserAction($id)
    {
//        throw new \Exception('test exception');
        return ['User'.$id => ['Jan', 'Test']];
    }

    /**
     * @return array
     */
    public function getUserNameAction($id)
    {
        return [$id, 'some name v2'];
    }

    public function postUserAction()
    {

    }

    public function putUserAction($id)
    {

    }

    public function deleteUserAction($id)
    {

    }

    /**
     * @return array
     */
    public function exceptionAction()
    {
        return ['exception'];
    }
}