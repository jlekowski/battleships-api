<?php

namespace AppBundle\Controller;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Repository\EventRepository;
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
     * @Operation(
     *     tags={"Event"},
     *     summary="Get event data",
     *     @SWG\Response(
     *         response="200",
     *         description="Event data received",
     *         @SWG\Schema(
     *             type="object",
     *             ref=@Model(type=Event::class)
     *         )
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Requested details someone else's game"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Game|Event not found"
     *     )
     * )
     *
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
     * @Operation(
     *     tags={"Event"},
     *     summary="Get events data",
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by type",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="gt",
     *         in="query",
     *         description="Filter by id greater than",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="player",
     *         in="query",
     *         description="Filter by player number",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Events data received",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref=@Model(type=Event::class))
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
     *
     * @Tag(expression="'game-' ~ game.getId() ~ '-events'")
     * @Security("game.belongsToUser(user)")
     * @QueryParam(
     *     name="type",
     *     requirements=@Assert\Choice(callback = {"AppBundle\Entity\Event", "getTypes"}, strict=true),
     *     nullable=true,
     *     strict=true,
     *     description="Filter by type"
     * )
     * @QueryParam(name="gt", requirements="\d+", nullable=true, strict=true, description="Filter by id greater than")
     * @QueryParam(name="player", requirements="[1-2]", nullable=true, strict=true, description="Filter by player number")
     *
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     * @return array|Event[]
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
     * @Operation(
     *     tags={"Event"},
     *     summary="Create new event",
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="type",
     *                 type="string",
     *                 example="shot"
     *             ),
     *             @SWG\Property(
     *                 property="value",
     *                 default=true,
     *                 example="B9"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="Event created",
     *         examples={
     *             "For ""chat"":":{"timestamp":"2016-11-11T16:21:16+0000"},
     *             "For ""shot"":":{"result":"miss"},
     *             "Otherwise empty:":""
     *         }
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Incorrect parameter provided"
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Game not found"
     *     ),
     *     @SWG\Response(
     *         response="409",
     *         description="Action not allow due to game flow restrictions"
     *     )
     * )
     *
     *
     * @Tag(expression="'game-' ~ game.getId() ~ '-events'")
     * @Security("game.belongsToUser(user)")
     * @RequestParam(
     *     name="type",
     *     requirements=@Assert\Choice(callback = {"AppBundle\Entity\Event", "getTypes"}, strict=true),
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
