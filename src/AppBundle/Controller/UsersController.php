<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UsersController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('', array('name' => $name));
    }

    /**
     * @return array
     */
    public function getUsersAction()
    {
//        throw new \Exception('test exception');

        $game = new Game();
        $game
            ->setPlayer1Hash('test1')
            ->setPlayer1Name('name1')
            ->setPlayer1Ships(['A1', 'A2'])
            ->setPlayer2Hash('test2')
            ->setPlayer2Name('name2')
            ->setPlayer2Ships(['A1', 'A2'])
        ;

//        /** @var GameRepository $gameRepository */
//        $gameRepository = $product = $this->getDoctrine()
//            ->getRepository('AppBundle:Games');
//
//        $gameRepository->

        $objectManager = $this->getDoctrine()->getManager();

        try {
            $objectManager->persist($game);
            $objectManager->flush();
        } catch (\Exception $e) {
            echo "<pre>";
            var_dump($e->getMessage());
            exit;
        }

        return ['gameId' => $game->getId()];
    }

    /**
     * @return array
     */
    public function getUserAction($id)
    {
//        throw new \Exception('test exception');
        return ['User' . $id => ['Jan', 'Test']];
    }

    /**
     * @return array
     */
    public function getUserNameAction($id)
    {
        return [$id, 'some name'];
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
