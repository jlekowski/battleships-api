<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use AppBundle\Http\Headers;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// @todo think about OAuth http://stackoverflow.com/questions/12672169/how-to-restfully-login-symfony2-security-fosuserbundle-fosrestbundle
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
 * /games/{id/hash}/events?type={event_type}&gt={id_last_event} GET
 * /games/{id/hash}/events/{event_id} GET
 * /games/{id/hash}/events POST
// * /games/{id/hash}/chats GET
// * /games/{id/hash}/chats POST
// * /games/{id/hash}/shots GET
// * /games/{id/hash}/shots POST
 */
class GameController extends FOSRestController
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
    public function __construct(EntityManagerInterface $entityManager, GameRepository $gameRepository)
    {
        $this->entityManager = $entityManager;
        $this->gameRepository = $gameRepository;
    }

    /**
     * @param Game $game
     * @return Game
     *
     * @Security("game.belongsToCurrentUser()")
     */
    public function getGameAction(Game $game)
    {
        return $game;
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @QueryParam(name="playerName", description="Player's name", allowBlank=false, strict=true, nullable=false)
     */
    public function postGameAction(ParamFetcher $paramFetcher)
    {
        echo "<pre>";
        var_dump($paramFetcher->get('playerName'));
        exit;
        $requestBag = $request->request;

        $player1Hash = hash('md5', uniqid(mt_rand(), true));
        $player2Hash = hash('md5', uniqid(mt_rand(), true));

        $game = new Game();
        $game
            ->setPlayer1Hash($player1Hash)
            ->setPlayer1Name($requestBag->get('playerName'))
            ->setPlayer1Ships($requestBag->get('playerShips', []))
            ->setPlayer2Hash($player2Hash)
            ->setPlayer2Name($requestBag->get('otherName', 'Player 2'))
            ->setPlayer2Ships($requestBag->get('otherShips', []))
        ;

//        $this->entityManager->persist($game);
//        $this->entityManager->flush();

        $view = $this
            ->routeRedirectView('api_v1_get_game', ['id' => $game->getId()])
            ->setHeader(Headers::GAME_TOKEN, $game->getPlayer1Hash()); // @todo To be removed once there's real authentication with Api-Token

        return $this->handleView($view);
    }

    /**
     * @todo Think about multiple patching (207 response status) http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
     *
     * @param Request $request
     * @param Game $game
     *
     * @Security("game.belongsToCurrentUser() && is_granted('patch', game)")
     */
    public function patchGameAction(Request $request, Game $game)
    {
        $this->updateGameFromArray($game, $request->request->all());

        $this->entityManager->persist($game);
        $this->entityManager->flush();
    }

    /**
     * @param Game $game
     * @param array $params
     */
    protected function updateGameFromArray(Game $game, array $params)
    {
        foreach ($params as $param => $value) {
            switch ($param) {
                case 'playerName':
                    $game->setPlayerName($value);
                    break;

                case 'playerShips':
                    $game->setPlayerShips($value);
                    break;
            }
        }
    }
}
