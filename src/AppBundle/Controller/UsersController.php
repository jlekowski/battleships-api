<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class UsersController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('', ['name' => $name]);
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
     * Converts an Exception to a Response.
     *
     * @param Request $request
     * @param FlattenException $exception
     * @param DebugLoggerInterface $logger
     *
     * @return array
     */
    public function exceptionAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        return ['code' => $exception->getStatusCode(), 'message' => $exception->getMessage(), 'class' => $exception->getClass()];
    }
}
