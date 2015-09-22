<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// @todo headers for specific version?
// @todo validation + exclusions on object properties (also depends on version)
//      http://symfony.com/doc/current/bundles/FOSRestBundle/param_fetcher_listener.html
//      http://symfony.com/doc/current/bundles/FOSRestBundle/annotations-reference.html
// @todo ApiDoc ? http://welcometothebundle.com/web-api-rest-with-symfony2-the-best-way-the-post-method/
/**
 * @todo what URI for shot? It's an update of game/{id/hash}|game/{id/hash}/shots resource and I need a result
 * @todo what URI for ships? It's an update of game/{id/hash} resource, or adding multiple game/{id/hash}/ships resources?
 * @todo maybe go with batch requests (to get game for example) https://parse.com/docs/rest/guide
 * /games POST
 * /games/{id/hash} GET
 * /games/{id/hash} PATCH
// * /games/{id/hash}/battle GET
 * /games/{id/hash} PATCH
 * /games/{id/hash}/events?type={event_type}&gt={id_last_event} GET
 * /games/{id/hash}/events/{event_id} GET
 * /games/{id/hash}/events POST
// * /games/{id/hash}/chats GET
// * /games/{id/hash}/chats POST
// * /games/{id/hash}/shots GET
// * /games/{id/hash}/shots POST
 */
class GamesController extends FOSRestController
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
        return $this->getGameById($id);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function postGameAction(Request $request)
    {
        $requestBag = $request->request;

        $hash = hash("md5", uniqid(mt_rand(), true));
        $tempHash = hash("md5", uniqid(mt_rand(), true));

        $game = new Game();
        $game
            ->setPlayer1Hash($hash)
            ->setPlayer1Name($requestBag->get('player1Name'))
            ->setPlayer1Ships($requestBag->get('player1Ships', []))
            ->setPlayer2Hash($tempHash)
            ->setPlayer2Name($requestBag->get('player2Name', 'Player 2'))
            ->setPlayer2Ships($requestBag->get('player2Ships', []))
        ;

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $view = $this->routeRedirectView('api_v1_get_game', ['id' => $game->getId()]);

        return $this->handleView($view);
    }

    /**
     * @todo Think about multiple patching (207 response status) http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
     *
     * @param Request $request
     * @param int $id
     */
    public function patchGameAction(Request $request, $id)
    {
        $game = $this->getGameById($id);
        $this->updateGameFromArray($game, $request->request->all());

        $this->entityManager->persist($game);
        $this->entityManager->flush();
    }

    /**
     * @param int $id
     * @return Game
     */
    private function getGameById($id)
    {
        return $this->gameRepository->find($id);
    }

    /**
     * @param Game $game
     * @param array $params
     */
    private function updateGameFromArray(Game $game, array $params)
    {
        foreach ($params as $param => $value) {
            switch ($param) {
                case 'player1Name':
                    $game->setPlayer1Name($value);
                    break;

                case 'player2Name':
                    $game->setPlayer2Name($value);
                    break;

                case 'player1Ships':
                    $game->setPlayer1Ships($value);
                    break;

                case 'player2Ships':
                    $game->setPlayer2Ships($value);
                    break;
            }
        }
    }
}
