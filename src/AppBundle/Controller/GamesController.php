<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use AppBundle\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

// @todo headers for specific version?
// @todo validation + exclusions on object properties (also depends on version)
//      http://symfony.com/doc/current/bundles/FOSRestBundle/param_fetcher_listener.html
//      http://symfony.com/doc/current/bundles/FOSRestBundle/annotations-reference.html
// @todo ApiDoc ? http://welcometothebundle.com/web-api-rest-with-symfony2-the-best-way-the-post-method/
class GamesController extends Controller
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var GameRepository
     */
    protected $gameRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param GameRepository $gameRepository
     */
    public function __construct(EntityManagerInterface $entityManager,  GameRepository $gameRepository)
    {
        $this->entityManager = $entityManager;
        $this->gameRepository = $gameRepository;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getGameAction($id)
    {
        return $this->gameRepository->find($id);
    }

    public function postGameAction(Request $request)
    {
        $requestBag = $request->request;

        $hash = hash("md5", uniqid(mt_rand(), true));
        $temphash = hash("md5", uniqid(mt_rand(), true));

        $game = new Game();
        $game
            ->setPlayer1Hash($hash)
            ->setPlayer1Name($requestBag->get('player1_name'))
            ->setPlayer1Ships([])
            ->setPlayer2Hash($temphash)
            ->setPlayer2Name($requestBag->get('player2_name', 'Player 2'))
            ->setPlayer2Ships([])
        ;

        $this->entityManager->persist($game);
        $this->entityManager->flush();

//        $response = new Response();
//        $response->setStatusCode(201);
//
//        // set the `Location` header only when creating new resources
//        $response->headers->set('Location',
//            $this->generateUrl(
//                'acme_demo_user_get', array('id' => $user->getId()),
//                true // absolute
//            )
//        );

        return ['gameId' => $game->getId()];
    }
}
