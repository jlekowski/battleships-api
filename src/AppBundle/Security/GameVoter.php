<?php

namespace AppBundle\Security;

use AppBundle\Battle\BattleManager;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\Voter\AbstractVoter;

class GameVoter extends AbstractVoter
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var BattleManager
     */
    protected $battleManager;

    /**
     * @param RequestStack $requestStack
     * @param BattleManager $battleManager
     */
    public function __construct(RequestStack $requestStack, BattleManager $battleManager)
    {
        $this->requestStack = $requestStack;
        $this->battleManager = $battleManager;
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
            'patch'
        ];
    }

    /**
     * @inheritdoc
     */
    protected function isGranted($attribute, $game, $user = null)
    {
        switch ($attribute) {
            case 'patch':
                $isPlayersTurn = $this->battleManager->whoseTurn($game) === $game->getPlayerNumber();
                $params = $this->getRequestBag()->all();
                $this->validatePatchParams($params, $isPlayersTurn);
                break;
        }
        return true;
    }

    private function validatePatchParams(array $params, $isPlayerTurn)
    {
        foreach ($params as $param) {
            switch ($param) {
                case 'playerName':
                    break;

                case 'other':
                    if (!$isPlayerTurn) {
                        throw new \Exception(sprintf('Param %s can be updated only when it\'s your turn', $param));
                    }
                    break;

                default:
                    throw new \Exception(sprintf('Param %s cannot be updated not', $param));
                    break;
            }
        }
    }

    /**
     * @return ParameterBag
     */
    private function getRequestBag()
    {
        return $this->requestStack->getCurrentRequest()->request;
    }
}
