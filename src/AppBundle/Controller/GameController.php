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
     * @RequestParam(name="playerShips", requirements="[A-J]([1-9]|10)", allowBlank=false, map=true)
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
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     *
     * @Tag(expression="'game-' ~ game.getId()")
     * @Tag(expression="'game-' ~ game.getId() ~ 'events'"))
     * @Tag("games")
     * @Security("request.request.get('joinGame') ? game.canJoin(user) : game.belongsToUser(user)")
     * @RequestParam(name="joinGame", requirements=@Assert\EqualTo("true"), allowBlank=true, nullable=true)
     * @RequestParam(name="playerShips", requirements="[A-J]([1-9]|10)", allowBlank=true, map=true)
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
