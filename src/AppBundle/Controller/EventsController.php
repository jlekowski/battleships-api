<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @param EntityManagerInterface $entityManager
     * @param EventRepository $eventRepository
     */
    public function __construct(EntityManagerInterface $entityManager, EventRepository $eventRepository)
    {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
    }

    /**
     * @param int $gameId
     * @param int $eventId
     * @return Event
     * @throws NotFoundHttpException
     */
    public function getEventAction($gameId, $eventId)
    {
        return $this->getEventByIdAndGameId($eventId, $gameId);
    }

    /**
     * @param Request $request
     * @param int $gameId
     * @return array
     */
    public function getEventsAction(Request $request, $gameId)
    {
        $gt = $request->query->get('gt');
        $eventType = $request->query->get('type');

        $criteria = new Criteria();
        $expr = $criteria->expr();

        $criteria->where($expr->eq('gameId', $gameId));
        if ($gt !== null) {
            $criteria->andWhere($expr->gt('id', $gt));
        }

        if ($eventType !== null) {
            $criteria->andWhere($expr->eq('type', $eventType));
        }

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        /** @var Event $event */
        $events = $eventRepository->matching($criteria);

        return $events;
    }

    /**
     * @param Request $request
     * @param int $gameId
     * @return Response
     * @throws \Exception
     */
    public function postEventAction(Request $request, $gameId)
    {
        $requestBag = $request->request;

        $event = new Event();
        $event
            ->setGameId($gameId)
            ->setPlayer(1)
            ->setType($requestBag->get('type'))
            ->setValue($requestBag->get('value', true))
        ;

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $view = $this->routeRedirectView('api_v1_get_game_event', ['gameId' => $gameId, 'eventId' => $event->getId()]);

        return $this->handleView($view);
    }

    /**
     * @param int $id
     * @param int $gameId
     * @return Event
     */
    protected function getEventByIdAndGameId($id, $gameId)
    {
        $event = $this->eventRepository->findOneBy(['id' => $id, 'gameId' => $gameId]);
        if (!$event) {
            throw $this->createNotFoundException();
        }
        return $event;
    }
}
