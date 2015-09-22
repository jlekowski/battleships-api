<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\GameRepository;
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
     * @var GameRepository
     */
    protected $gameRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param GameRepository $gameRepository
     */
    public function __construct(EntityManagerInterface $entityManager,  GameRepository $gameRepository)
    {
        $this->entityManager = $entityManager;
        $this->gameRepository = $gameRepository;
    }

    /**
     * @param int $gameId
     * @param int $eventId
     * @return Event
     * @throws NotFoundHttpException
     */
    public function getEventAction($gameId, $eventId)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        /** @var Event $event */
        $event = $eventRepository->find($eventId);
        if ($event->getGameId() !== $gameId) {
            throw $this->createNotFoundException();
        }

        return $event;
    }

    /**
     * @param Request $request
     * @param int $gameId
     * @return array
     */
    public function getEventsAction(Request $request, $gameId)
    {
        return [];
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
        $eventType = $requestBag->get('eventType');

        switch ($eventType) {
            case Event::TYPE_CHAT:
            case Event::TYPE_SHOT:
            case Event::TYPE_START_GAME:
                break;

            case Event::TYPE_JOIN_GAME:
            case Event::TYPE_NAME_UPDATE:
                throw new \Exception('Incorrect event type', Codes::HTTP_BAD_REQUEST);

            default:
                throw new \Exception('No such event type', Codes::HTTP_BAD_REQUEST);
        }

        $event = new Event();
        $event
            ->setGameId($gameId)
            ->setPlayer($requestBag->get('player'))
            ->setEventType($eventType)
            ->setEventValue($requestBag->get('eventValue', true))
        ;

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        $view = $this->routeRedirectView('api_v1_get_game_event', ['gameId' => $gameId, 'eventId' => $event->getId()]);

        return $this->handleView($view);
    }
}
