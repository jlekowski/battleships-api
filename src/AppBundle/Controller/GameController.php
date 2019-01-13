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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Swagger\Annotations as SWG;
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
     * @Operation(
     *     tags={"Game"},
     *     summary="Get game data",
     *     @SWG\Response(
     *         response="200",
     *         description="Game data received",
     *         @SWG\Schema(
     *             type="object",
     *             ref=@Model(type=Game::class)
     *         )
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Requested details someone else's game"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Game not found"
     *     )
     * )
     *
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
     * @Operation(
     *     tags={"Game"},
     *     summary="Get list of games",
     *     @SWG\Parameter(
     *         name="available",
     *         in="query",
     *         description="Filter games available to join",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Games data received",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref=@Model(type=Game::class))
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Parameter 'available' is not true"
     *     )
     * )
     *
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
     * @Operation(
     *     tags={"Game"},
     *     summary="Create new game",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=false,
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="playerShips",
     *                 type="array",
     *                 @SWG\Items(type="string"),
     *                 example={"A1","C2","D2","F2","H2","J2","F5","F6","I6","J6","A7","B7","C7","F7","F8","I9","J9","E10","F10","G10"}
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="Game created"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Incorrect 'playerShips' provided"
     *     )
     * )
     *
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
     * @Operation(
     *     tags={"Game"},
     *     summary="Update game",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=false,
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="joinGame",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @SWG\Property(
     *                 property="playerShips",
     *                 type="array",
     *                 @SWG\Items(type="string"),
     *                 example={"A1","C2","D2","F2","H2","J2","F5","F6","I6","J6","A7","B7","C7","F7","F8","I9","J9","E10","F10","G10"}
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Game updated"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Incorrect parameter provided"
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="No access to game"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Game not found"
     *     )
     * )
     *
     *
     * @Tag(expression="'game-' ~ game.getId()")
     * @Tag(expression="'game-' ~ game.getId() ~ '-events'"))
     * @Tag("games")
     * @Security(
     *     "request.request.get('joinGame') ? game.canJoin(user) : game.belongsToUser(user)",
     *     message="User cannot join the game or game does not belong to the user"
     * )
     * @RequestParam(name="joinGame", requirements=@Assert\EqualTo("true"), allowBlank=true, nullable=true)
     * @RequestParam(name="playerShips", requirements="[A-J]([1-9]|10)", allowBlank=true, nullable=true, map=true)
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
