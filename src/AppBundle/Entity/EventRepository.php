<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends EntityRepository
{
    /**
     * @param Game $game
     * @param string $eventType
     * @param int $gt With Id greater than
     * @param int $player 1|2
     * @return Event[]|Collection
     */
    public function findForGameByType(Game $game, $eventType = null, $gt = null, $player = null)
    {
        $criteria = $this->createFindForGameByTypeAndPlayerCriteria($game, $eventType, $player);
        if ($gt !== null) {
            $criteria->andWhere(Criteria::expr()->gt('id', $gt));
        }

        return $this->matching($criteria);
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @param int $player 1|2
     * @return Collection|Event[]
     */
    public function findForGameByTypeAndPlayer(Game $game, $eventType, $player)
    {
        $criteria = $this->createFindForGameByTypeAndPlayerCriteria($game, $eventType, $player);

        return $this->matching($criteria);
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @return Event
     */
    public function findLastForGameByType(Game $game, $eventType)
    {
        $criteria = $this->createFindForGameByTypeAndPlayerCriteria($game, $eventType)
            ->setMaxResults(1)
            ->orderBy(['id' => 'DESC'])
        ;

        return $this->matching($criteria)->first();
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @param int $player 1|2
     * @return Criteria
     */
    private function createFindForGameByTypeAndPlayerCriteria(Game $game, $eventType = null, $player = null)
    {
        $criteria = new Criteria();
        $expr = Criteria::expr();

        $criteria->where($expr->eq('game', $game));
        if ($eventType !== null) {
            $criteria->andWhere($expr->eq('type', $eventType));
        }

        if ($player !== null) {
            $criteria->andWhere($expr->eq('player', $player));
        }

        return $criteria;
    }
}
