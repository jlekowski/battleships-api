<?php

namespace AppBundle\Controller;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\HttpCacheBundle\Configuration\Tag;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

class EventController extends FOSRestController
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var GameRepository
     */
    protected $gameRepository;

    /**
     * @var BattleManager
     */
    protected $battleManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param EventRepository $eventRepository
     * @param BattleManager $battleManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        BattleManager $battleManager
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->battleManager = $battleManager;
    }

    /**
     * Example response:<pre>
     * {
     *     "id": 1,
     *     "player": 1,
     *     "type": "start_game",
     *     "value": "1",
     *     "timestamp": "2016-10-18T16:08:37+0000"
     * }</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Get event data",
     *  section="Event",
     *  requirements={
     *     {"name"="game", "dataType"="integer", "requirement"="\d+", "required"=true, "description"="Game id"},
     *     {"name"="event", "dataType"="integer", "requirement"="\d+", "required"=true, "description"="Event id"}
     *  },
     *  statusCodes = {
     *     200="Event data received",
     *     403="Requested details someone else's game",
     *     404="Game|Event not found",
     *   }
     * )
     *
     * @Security("game.belongsToUser(user) && (game === event.getGame())")
     *
     * @param Game $game
     * @param Event $event
     * @return Event
     */
    public function getEventAction(Game $game, Event $event)
    {
        return $event;
    }

    /**
     * Example response:<pre>
     * [
     *     {
     *         "id": 5,
     *         "player": 2,
     *         "type": "shot",
     *         "value": "A10|sunk",
     *         "timestamp": "2016-10-18T16:08:47+0000"
     *     },
     *     ...
     * ]</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Get events data",
     *  section="Event",
     *  requirements={
     *     {"name"="game", "dataType"="integer", "requirement"="\d+", "required"=true, "description"="Game id"}
     *  },
     *  statusCodes = {
     *     200="Events data received",
     *     403="Requested details someone else's game",
     *     404="Game not found"
     *   }
     * )
     *
     * @Tag(expression="'game-' ~ game.getId() ~ '-events'")
     * @Security("game.belongsToUser(user)")
     * @QueryParam(
     *     name="type",
     *     requirements=@Assert\Choice(callback = {"AppBundle\Entity\Event", "getTypes"}),
     *     nullable=true,
     *     strict=true,
     *     description="Filter by type"
     * )
     * @QueryParam(name="gt", requirements="\d+", nullable=true, strict=true, description="Filter by id greater than")
     * @QueryParam(name="player", requirements="[1-2]", nullable=true, strict=true, description="Filter by player number")
     *
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     * @return array
     */
    public function getEventsAction(ParamFetcher $paramFetcher, Game $game)
    {
        return $this->eventRepository->findForGameByType(
            $game,
            $paramFetcher->get('type'),
            $paramFetcher->get('gt'),
            $paramFetcher->get('player')
        )->toArray();
    }

    /**
     * Example response:<pre>
     *  {"timestamp":"2016-11-11T16:21:16+0000"} # for chat
     *  {"result":"miss"} # for shot</pre>
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Create new event",
     *  section="Event",
     *  statusCodes={
     *     201="Event created",
     *     400="Incorrect parameter provided",
     *     404="Game not found",
     *     409="Action not allow due to game flow restrictions"
     *  }
     * )
     *
     * @Tag(expression="'game-' ~ game.getId() ~ '-events'")
     * @Security("game.belongsToUser(user)")
     * @RequestParam(
     *     name="type",
     *     requirements=@Assert\Choice(callback = {"AppBundle\Entity\Event", "getTypes"})
     * )
     * @RequestParam(name="value", requirements=".*\S.*", allowBlank=false, default=true)
     *
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     * @return Response
     */
    public function postEventAction(ParamFetcher $paramFetcher, Game $game)
    {
        $event = new Event();
        $event
            ->setGame($game)
            ->setPlayer($game->getPlayerNumber())
            ->setType($paramFetcher->get('type'))
            ->setValue($paramFetcher->get('value'))
        ;

        $data = null;
        switch ($event->getType()) {
            case Event::TYPE_CHAT:
                $event->applyCurrentTimestamp();
                $data = ['timestamp' => $event->getTimestamp()];
                break;

            case Event::TYPE_SHOT:
                $shotResult = $this->battleManager->getShotResult($event);
                $event->setValue([$event->getValue(), $shotResult]);
                $data = ['result' => $shotResult];
                break;
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $view = $this
            ->routeRedirectView('api_v1_get_game_event', ['game' => $game->getId(), 'event' => $event->getId()])
            ->setData($data)
        ;

        return $this->handleView($view);
    }
}
