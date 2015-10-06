<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * @todo check difference for this->matching() and game->getEvents() (how if game->getEvents()->toArray() beforehand?)
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
     * @return Collection|Event[]
     */
    public function findForGameByType(Game $game, $eventType = null, $gt = null)
    {
        $criteria = $this->createFindForGameByTypeCriteria($game, $eventType);
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
        $criteria = $this->createFindForGameByTypeCriteria($game, $eventType)
            ->andWhere(Criteria::expr()->eq('player', $player))
        ;

        return $this->matching($criteria);
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @return Event
     */
    public function findLastForGameByType(Game $game, $eventType)
    {
        $criteria = $this->createFindForGameByTypeCriteria($game, $eventType)
            ->setMaxResults(1)
            ->orderBy(['id' => 'DESC'])
        ;

        return $this->matching($criteria)->first();
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @return Criteria
     */
    private function createFindForGameByTypeCriteria(Game $game, $eventType = null)
    {
        $criteria = new Criteria();
        $expr = Criteria::expr();

        $criteria->where($expr->eq('game', $game));
        if ($eventType !== null) {
            $criteria->andWhere($expr->eq('type', $eventType));
        }

        return $criteria;
    }
}
