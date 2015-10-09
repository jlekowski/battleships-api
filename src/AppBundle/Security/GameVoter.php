<?php

namespace AppBundle\Security;

use AppBundle\Battle\BattleManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;

class GameVoter extends AbstractVoter
{
    const PATCH = 'patch';
    const POST_EVENT = 'post_event';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var BattleManager
     */
    protected $battleManager;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @param RequestStack $requestStack
     * @param BattleManager $battleManager
     * @param EventRepository $eventRepository
     */
    public function __construct(
        RequestStack $requestStack,
        BattleManager $battleManager,
        EventRepository $eventRepository
    ) {
        $this->requestStack = $requestStack;
        $this->battleManager = $battleManager;
        $this->eventRepository = $eventRepository;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedClasses()
    {
        return [
            'AppBundle\Entity\Game'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedAttributes()
    {
        return [
            self::PATCH,
            self::POST_EVENT
        ];
    }

    /**
     * @param string $attribute
     * @param Game $game
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    protected function isGranted($attribute, $game, $user = null)
    {
        switch ($attribute) {
            case self::PATCH:
                $params = $this->getRequestBag()->all();
                $this->checkGamePatchParams($params, $game);
                break;

            case self::POST_EVENT:
                $eventType = $this->getRequestBag()->get('type');
                $this->checkPostEventParams($eventType, $game);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Incorrect security attribute for game: %s', $attribute));
        }

        return true;
    }

    private function checkGamePatchParams(array $params, Game $game)
    {
        foreach ($params as $param) {
            switch ($param) {
                case 'playerName':
                    break;

                case 'playerShips':
                    if ($this->hasGameAlreadyStarted($game)) {
                        throw new \Exception(sprintf('Property `%s` cannot be updated after the game has started', $param));
                    }
                    break;

                default: // @todo should I really check here random params?
                    throw new \Exception(sprintf('Property `%s` cannot be updated', $param));
            }
        }
    }

    private function checkPostEventParams($eventType, Game $game)
    {
        switch ($eventType) {
            case Event::TYPE_SHOT:
                $isPlayerTurn = $this->battleManager->whoseTurn($game) === $game->getPlayerNumber();
                if (!$isPlayerTurn) {
                    throw new \Exception(sprintf('Event type `%s` can be created only when it\'s your turn', $eventType));
                }
                break;

            case Event::TYPE_JOIN_GAME:
            case Event::TYPE_START_GAME:
            case Event::TYPE_NAME_UPDATE:
            case Event::TYPE_CHAT:
            default:
                break;
        }
    }

    /**
     * @param Game $game
     * @return bool
     */
    private function hasGameAlreadyStarted(Game $game)
    {
        $gameStartEvents = $this->eventRepository->findForGameByTypeAndPlayer(
            $game,
            Event::TYPE_START_GAME,
            $game->getPlayerNumber()
        );

        return !$gameStartEvents->isEmpty();
    }

    /**
     * @return ParameterBag
     */
    private function getRequestBag()
    {
        return $this->requestStack->getCurrentRequest()->request;
    }
}
