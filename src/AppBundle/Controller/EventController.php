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
     * @param Game $game
     * @param Event $event
     * @return Event
     *
     * @Security("game.belongsToUser(user) && (game === event.getGame())")
     */
    public function getEventAction(Game $game, Event $event)
    {
        return $event;
    }

    /**
     * @todo check if query is optimised enough
     *
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     * @return array
     *
     * @Tag(expression="'game-' ~ game.getId() ~ 'events'")
     * @Security("game.belongsToUser(user)")
     * @QueryParam(
     *     name="type",
     *     requirements=@Assert\Choice(
     *         {Event::TYPE_CHAT, Event::TYPE_SHOT, Event::TYPE_JOIN_GAME, Event::TYPE_START_GAME, Event::TYPE_NAME_UPDATE, Event::TYPE_NEW_GAME}
     *     ),
     *     nullable=true,
     *     strict=true
     * )
     * @QueryParam(name="gt", requirements="\d+", nullable=true, strict=true)
     * @QueryParam(name="player", requirements="[1-2]", nullable=true, strict=true)
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
     * @todo all requirements="\S.*" should either trim, or check first last character to be non-whitespace
     *
     * @param ParamFetcher $paramFetcher
     * @param Game $game
     * @return Response
     *
     * @Tag(expression="'game-' ~ game.getId() ~ 'events'")
     * @Security("game.belongsToUser(user)")
     * @RequestParam(
     *     name="type",
     *     requirements=@Assert\Choice(
     *         {Event::TYPE_CHAT, Event::TYPE_SHOT, Event::TYPE_JOIN_GAME, Event::TYPE_START_GAME, Event::TYPE_NAME_UPDATE, Event::TYPE_NEW_GAME}
     *     ),
     *     allowBlank=false
     * )
     * @RequestParam(name="value", requirements="\S.*", allowBlank=false, default=true)
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
                $event->setValue(implode('|', [$event->getValue(), $shotResult]));
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
