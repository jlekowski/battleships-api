<?php

namespace AppBundle\Controller;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @todo maybe validate params http://symfony.com/doc/current/bundles/FOSRestBundle/param_fetcher_listener.html
 */
class EventsController extends FOSRestController
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
     */
    public function __construct(EntityManagerInterface $entityManager, EventRepository $eventRepository, BattleManager $battleManager)
    {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->battleManager = $battleManager;
    }

    /**
     * @param Game $game
     * @param Event $event
     * @return Event
     *
     * @Security("game.belongsToCurrentUser() && (game === event.getGame())")
     */
    public function getEventAction(Game $game, Event $event)
    {
        return $event;
    }

    /**
     * @param Request $request
     * @param Game $game
     * @return \Doctrine\Common\Collections\Collection
     *
     * @Security("game.belongsToCurrentUser()")
     */
    public function getEventsAction(Request $request, Game $game)
    {
        $gt = $request->query->get('gt');
        $eventType = $request->query->get('type');

        $criteria = new Criteria();
        $expr = $criteria->expr();

        $criteria->where($expr->eq('game', $game));
        if ($gt !== null) {
            $criteria->andWhere($expr->gt('id', $gt));
        }
        if ($eventType !== null) {
            $criteria->andWhere($expr->eq('type', $eventType));
        }

        return $this->eventRepository->matching($criteria);
    }

    /**
     * @param Request $request
     * @param Game $game
     * @return Response
     * @throws \Exception
     *
     * @Security("game.belongsToCurrentUser()")
     */
    public function postEventAction(Request $request, Game $game)
    {
        $requestBag = $request->request;

        $event = new Event();
        $event
            ->setGame($game)
            ->setPlayer($game->getPlayerNumber())
            ->setType($requestBag->get('type'))
            ->setValue($requestBag->get('value', true))
        ;

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $view = $this->routeRedirectView(
            'api_v1_get_game_event',
            ['game' => $game->getId(), 'event' => $event->getId()]
        );

        switch ($event->getType()) {
            case Event::TYPE_CHAT:
                $view->setData(['timestamp' => $event->getTimestamp()]);
                break;

            case Event::TYPE_SHOT:
                $result = $this->battleManager->getShotResult($event);
                $view->setData(['result' => $result]);
                break;
        }

        return $this->handleView($view);
    }
}
