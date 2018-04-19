<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Game;
use AppBundle\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;

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
     * Example response:<pre>
     * {
     *     "player": {
     *         "name": "My Player"
     *     },
     *     "other": {
     *         "name": "Opponent Name"
     *     },
     *     "playerShips": ["A1", "C2", "D2", "F2", "H2", "J2", "F5", "F6", "I6", "J6", "A7", "B7", "C7", "F7", "F8", "I9", "J9", "E10", "F10", "G10"],
     *     "playerNumber": 1,
     *     "id": 1,
     *     "timestamp": "2016-10-18T16:08:32+0000"
     * }</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Get game data",
     *  section="Game",
     *  requirements={
     *     {"name"="game", "dataType"="integer", "requirement"="\d+", "required"=true, "description"="Game id"}
     *  },
     *  statusCodes = {
     *     200="Game data received",
     *     403="Requested details someone else's game",
     *     404="Game not found",
     *   }
     * )
     * @Tag(expression="'game-' ~ game.getId()")
     * @Security("game.belongsToUser(user) || game.canJoin(user)")
     *
     * @param Game $game
     * @return Game
     */
    public function getGameAction(Game $game)
    {
        return $game;
    }

    /**
     * Example response:<pre>
     * [
     *     {
     *         "other": {
     *             "name": "New Player"
     *         },
     *         "playerShips": ["A1", "C2", "D2", "F2", "H2", "J2", "F5", "F6", "I6", "J6", "A7", "B7", "C7", "F7", "F8", "I9", "J9", "E10", "F10", "G10"],
     *         "playerNumber": 2,
     *         "id": 102,
     *         "timestamp": "2016-10-24T14:26:48+0000"
     *     },
     *     ...
     * ]</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Get list of games",
     *  section="Game",
     *  statusCodes={
     *     200="Games data received",
     *     400="Parameter 'available' is not true"
     *  }
     * )
     * @Tag("games")
     * @QueryParam(name="available", requirements=@Assert\EqualTo("true"), nullable=true, strict=true, description="Filter games available to join")
     *
     * @param ParamFetcher $paramFetcher
     * @return Response
     * @throws BadRequestHttpException
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
     * Example request in <strong>Content</strong> (not required):
     *  <pre>{"playerShips":["A1","C2","D2","F2","H2","J2","F5","F6","I6","J6","A7","B7","C7","F7","F8","I9","J9","E10","F10","G10"]}</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Create new game",
     *  section="Game",
     *  statusCodes={
     *     201="Game created",
     *     400="Incorrect 'playerShips' provided"
     *  }
     * )
     *
     * @Tag("games")
     * @RequestParam(name="playerShips", requirements="[A-J]([1-9]|10)", allowBlank=false, nullable=true, map=true)
     *
     * @param ParamFetcher $paramFetcher
     * @return Response
     */
    public function postGameAction(ParamFetcher $paramFetcher)
    {
        $user = $this->getUser();

        $playerShips = (array)$paramFetcher->get('playerShips');
        $game = new Game();
        $game
            ->setLoggedUser($user)
            ->setUser1($user)
            ->setPlayerShips($playerShips)
        ;

        $this->entityManager->persist($game);
        if ($playerShips) {
            $this->createEvent($game, Event::TYPE_START_GAME);
        }
        $this->entityManager->flush();

        $view = $this->routeRedirectView('api_v1_get_game', ['game' => $game->getId()]);

        return $this->handleView($view);
    }

    /**
     * Example request in <strong>Content</strong>:
     *  <pre>{"joinGame":true,"playerShips":["A1","C2","D2","F2","H2","J2","F5","F6","I6","J6","A7","B7","C7","F7","F8","I9","J9","E10","F10","G10"]}</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Update game",
     *  section="Game",
     *  statusCodes={
     *     204="Game updated",
     *     400="Incorrect parameter provided",
     *     403="No access to game",
     *     404="Game not found",
     *  }
     * )
     *
     * @Tag(expression="'game-' ~ game.getId()")
     * @Tag(expression="'game-' ~ game.getId() ~ '-events'"))
     * @Tag("games")
     * @Security(
     *     "request.request.get('joinGame') ? game.canJoin(user) : game.belongsToUser(user)",
     *     message="User cannot join the game or game does not belong to the user"
     * )
     * @RequestParam(name="joinGame", requirements=@Assert\EqualTo("true"), allowBlank=true, nullable=true)
     * @RequestParam(name="playerShips", requirements="[A-J]([1-9]|10)", allowBlank=true, map=true)
     *
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     */
    public function patchGameAction(ParamFetcher $paramFetcher, Game $game)
    {
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
