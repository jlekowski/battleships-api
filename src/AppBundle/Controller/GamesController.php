<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

// @todo headers for specific version?
// @todo validation + exclusions on object properties (also depends on version)
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
        var_dump($request->getContent(), $request->request->all());
        exit;

        $game = new Game();
        $game
            ->setPlayer1Hash('test1')
            ->setPlayer1Name('name1')
            ->setPlayer1Ships(['A1', 'A2'])
            ->setPlayer2Hash('test2')
            ->setPlayer2Name('name2')
            ->setPlayer2Ships(['A1', 'A2'])
        ;

//        $this->entityManager->persist($game);
//        $this->entityManager->flush();

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
