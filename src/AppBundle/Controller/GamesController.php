<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// @todo headers for specific version?
// @todo validation + exclusions on object properties (also depends on version)
//      http://symfony.com/doc/current/bundles/FOSRestBundle/param_fetcher_listener.html
//      http://symfony.com/doc/current/bundles/FOSRestBundle/annotations-reference.html
// @todo ApiDoc ? http://welcometothebundle.com/web-api-rest-with-symfony2-the-best-way-the-post-method/
/*
 * events
 *   name_update
 *   start_game
 *   shot
 *   chat
 *   join_game
 */
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
     * @return Game
     */
    public function getGameAction($id)
    {
        return $this->gameRepository->find($id);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function postGameAction(Request $request)
    {
        $requestBag = $request->request;

        $hash = hash("md5", uniqid(mt_rand(), true));
        $temphash = hash("md5", uniqid(mt_rand(), true));

        $game = new Game();
        $game
            ->setPlayer1Hash($hash)
            ->setPlayer1Name($requestBag->get('player1_name'))
//            ->setPlayer1Ships([])
            ->setPlayer2Hash($temphash)
            ->setPlayer2Name($requestBag->get('player2_name', 'Player 2'))
//            ->setPlayer2Ships([])
        ;

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $response = new Response();
        $response->setStatusCode(201);

        $response->headers->set(
            'Location',
            $this->generateUrl('v1_get_game', array('id' => $game->getId()), UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return $response;
    }

    /**
     * @todo Think about multiple patching (207 response status) http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
     *
     * @param Request $request
     * @param int $id
     */
    public function patchGameAction(Request $request, $id)
    {
        /** @var Game $game */
        $game = $this->gameRepository->find($id);
        $this->updateGameFromArray($game, $request->request->all());

        $this->entityManager->persist($game);
        $this->entityManager->flush();
    }

    /**
     * @param Game $game
     * @param array $params
     */
    private function updateGameFromArray(Game $game, array $params)
    {
        foreach ($params as $param => $value) {
            switch ($param) {
                case 'player1_name':
                    $game->setPlayer1Name($value);
                    break;

                case 'player2_name':
                    $game->setPlayer2Name($value);
                    break;

                case 'player1_ships':
                    $game->setPlayer1Ships($value);
                    break;

                case 'player2_ships':
                    $game->setPlayer2Ships($value);
                    break;
            }
        }
    }

    private function checkGameUpdates(Game $game)
    {
//        $game = $this->gameRepository->find($id);
//        $game->setPlayer2Name('aaa');
        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets();
        echo "<pre>";
        var_dump($uow->getEntityChangeSet($game));
        exit;
    }
}
