<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Entity\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// @todo headers for specific version?
// @todo validation + exclusions on object properties (also depends on version)
//      http://symfony.com/doc/current/bundles/FOSRestBundle/param_fetcher_listener.html
//      http://symfony.com/doc/current/bundles/FOSRestBundle/annotations-reference.html
// @todo ApiDoc ? http://welcometothebundle.com/web-api-rest-with-symfony2-the-best-way-the-post-method/
/**
 * /games POST
 * /games/{id/hash} GET
 * /games/{id/hash} PATCH
 * /games/{id/hash}/ships POST
 * /games/{id/hash}/chats POST
 * /games/{id/hash}/shots POST
 * /games/{id/hash}/events?gt={id_last_event} GET
 *
 * Class GamesController
 * @package AppBundle\Controller
 */
class GamesController extends Controller
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
     * @param int $id
     * @return Game
     */
    public function getGameAction($id)
    {
        return $this->getGameById($id);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function postGameAction(Request $request)
    {
        $requestBag = $request->request;

        $hash = hash("md5", uniqid(mt_rand(), true));
        $temphash = hash("md5", uniqid(mt_rand(), true));

        $game = new Game();
        $game
            ->setPlayer1Hash($hash)
            ->setPlayer1Name($requestBag->get('player1_name'))
            ->setPlayer1Ships($requestBag->get('player1_ships'))
            ->setPlayer2Hash($temphash)
            ->setPlayer2Name($requestBag->get('player2_name', 'Player 2'))
            ->setPlayer2Ships($requestBag->get('player2_ships'))
        ;

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $response = new Response();
        $response->setStatusCode(201);

        $response->headers->set(
            'Location',
            $this->generateUrl('v1_get_game', ['id' => $game->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return $response;
    }

    /**
     * @todo Think about multiple patching (207 response status) http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
     *
     * @param Request $request
     * @param int $id
     */
    public function patchGameAction(Request $request, $id)
    {
        $game = $this->getGameById($id);
        $this->updateGameFromArray($game, $request->request->all());

        $this->entityManager->persist($game);
        $this->entityManager->flush();
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function postGameShipAction(Request $request, $id)
    {
        $game = $this->getGameById($id);
        $requestBag = $request->request;

        $game
            ->setPlayer1Ships($requestBag->get('player1_ships'))
            ->setPlayer2Ships($requestBag->get('player2_ships'));

        return (new Response())->setStatusCode(201);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function postGameChatAction(Request $request, $id)
    {
        $game = $this->getGameById($id);
        $requestBag = $request->request;

        $event = new Event();
        $event
            ->setGameId($game->getId())
            ->setPlayer($requestBag->get('player'))
            ->setEventType(EVENT::TYPE_CHAT)
            ->setEventValue($requestBag->get('text'))
        ;

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return (new Response())->setStatusCode(201);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function postGameShotAction(Request $request, $id)
    {
        $game = $this->getGameById($id);
        $requestBag = $request->request;

        $event = new Event();
        $event
            ->setGameId($game->getId())
            ->setPlayer($requestBag->get('player'))
            ->setEventType(EVENT::TYPE_SHOT)
            ->setEventValue($requestBag->get('shot'))
        ;

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return (new Response())->setStatusCode(201);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function getGameEventsAction(Request $request, $id)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->getDoctrine()->getRepository('AppBundle:Event');
        $eventRepository->find($id);

        return ['list of events'];
    }

    /**
     * @param int $id
     * @return Game
     */
    private function getGameById($id)
    {
        return $this->gameRepository->find($id);
    }

    /**
     * @param Game $game
     * @param array $params
     */
    private function updateGameFromArray(Game $game, array $params)
    {
        foreach ($params as $param => $value) {
            switch ($param) {
                case 'player1_name':
                    $game->setPlayer1Name($value);
                    break;

                case 'player2_name':
                    $game->setPlayer2Name($value);
                    break;

                case 'player1_ships':
                    $game->setPlayer1Ships($value);
                    break;

                case 'player2_ships':
                    $game->setPlayer2Ships($value);
                    break;
            }
        }
    }
}
