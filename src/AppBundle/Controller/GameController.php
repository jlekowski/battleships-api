<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;

// @todo test caching games, events, available games etc.
// @todo check strictly for URI, e.g. /v1/games/1b
// @todo think about OAuth http://stackoverflow.com/questions/12672169/how-to-restfully-login-symfony2-security-fosuserbundle-fosrestbundle
// @todo headers for specific version?
// @todo exclusions on object properties depends on version
// @todo ApiDoc ? http://welcometothebundle.com/web-api-rest-with-symfony2-the-best-way-the-post-method/
/**
 * @todo exception when accessing not existing game currently is "AppBundle\\Entity\\Game object not found"
 * @todo what URI for shot? It's an update of game/{id/hash}|game/{id/hash}/shots resource and I need a result
 * @todo what URI for ships? It's an update of game/{id/hash} resource, or adding multiple game/{id/hash}/ships resources?
 * @todo maybe go with batch requests (to get game for example) https://parse.com/docs/rest/guide
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
     * @Tag(expression="'game-' ~ game.getId()")
     * @Security("game.belongsToUser(user) || game.canJoin(user)")
     */
    public function getGameAction(Game $game)
    {
        return $game;
    }

    /**
     * @param ParamFetcher $paramFetcher
     * @return Response
     * @throws BadRequestHttpException
     *
     * @Tag("games")
     * @QueryParam(name="available", requirements=@Assert\EqualTo("true"), nullable=true, strict=true)
     */
    public function getGamesAction(ParamFetcher $paramFetcher)
    {
        if ($paramFetcher->get('available')) {
            return $this->getAvailableGamesAction();
        }

        throw new BadRequestHttpException('Invalid request - must use \'?available=true\'');
    }

    /**
     * @return Response
     */
    protected function getAvailableGamesAction()
    {
        $newGamesFilter = function (Game $game) {
            return $game->isAvailable();
        };

        $games = $this->gameRepository->findAvailableForUser($this->getUser(), 5)->filter($newGamesFilter);
        $view = $this->view($games);

        /** @var Game $oldestGame */
        $oldestGame = $games->last();
        if ($oldestGame) {
            // if games found, cache expires oldest game + 5 minutes (if searched for games withing last 5 minutes)
            $maxAge = Game::JOIN_LIMIT - (time() - $oldestGame->getTimestamp()->getTimestamp());
            $view->getResponse()->setSharedMaxAge($maxAge);
        }

        return $this->handleView($view);
    }

    /**
     * @param ParamFetcher $paramFetcher
     * @return Response
     *
     * @Tag("games")
     * @RequestParam(name="playerShips", requirements="[A-J]([1-9]|10)", allowBlank=false, nullable=true, array=true)
     */
    public function postGameAction(ParamFetcher $paramFetcher)
    {
        $user = $this->getUser();

        $game = new Game();
        $game
            ->setLoggedUser($user)
            ->setUser1($user)
            ->setPlayerShips($paramFetcher->get('playerShips'))
        ;

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $view = $this->routeRedirectView('api_v1_get_game', ['game' => $game->getId()]);

        return $this->handleView($view);
    }

    /**
     * @todo Think about multiple patching (207 response status) http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
     * @todo specific response (http code?)
     * @todo be more specific when clearing cache (setting ships does not require that?)
     *
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     *
     * @Tag(expression="'game-' ~ game.getId()")
     * @Tag("events")
     * @Security("request.request.get('joinGame') ? game.canJoin(user) : game.belongsToUser(user)")
     * @RequestParam(name="joinGame", requirements=@Assert\EqualTo("true"), allowBlank=false, nullable=true)
     * @RequestParam(name="playerShips", requirements="[A-J]([1-9]|10)", allowBlank=false, nullable=true, array=true)
     */
    public function patchGameAction(ParamFetcher $paramFetcher, Game $game)
    {
        // @todo - do we need both events if both parameters set in one request?
        if ($paramFetcher->get('joinGame')) {
            $game->setUser2($this->getUser());
            $this->createEvent($game, Event::TYPE_JOIN_GAME);
        }

        $playerShips = $paramFetcher->get('playerShips');
        if ($playerShips) {
            $game->setPlayerShips($playerShips);
            $this->createEvent($game, Event::TYPE_START_GAME);
        }

        $this->entityManager->persist($game);
        $this->entityManager->flush();
    }

    /**
     * @todo maybe go with subrequest to create event?
     *
     * @param Game $game
     * @param string $eventType
     */
    private function createEvent(Game $game, $eventType)
    {
        $event = new Event();
        $event
            ->setGame($game)
            ->setPlayer($game->getPlayerNumber())
            ->setType($eventType)
        ;
        $this->entityManager->persist($event);
    }
}
