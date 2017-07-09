<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Event;
use AppBundle\Repository\EventRepository;
use AppBundle\Exception\UnexpectedEventTypeException;

class BattleManager
{
    const SHOT_RESULT_MISS = 'miss';
    const SHOT_RESULT_HIT = 'hit';
    const SHOT_RESULT_SUNK = 'sunk';

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var CoordsManager
     */
    protected $coordsManager;

    /**
     * @param EventRepository $eventRepository
     * @param CoordsManager $coordsManager
     */
    public function __construct(EventRepository $eventRepository, CoordsManager $coordsManager)
    {
        $this->eventRepository = $eventRepository;
        $this->coordsManager = $coordsManager;
    }

    /**
     * @param Event $shotEvent
     * @return string miss|hit|sunk
     * @throws UnexpectedEventTypeException
     */
    public function getShotResult(Event $shotEvent)
    {
        if ($shotEvent->getType() !== Event::TYPE_SHOT) {
            throw new UnexpectedEventTypeException($shotEvent->getType(), Event::TYPE_SHOT);
        }

        $game = $shotEvent->getGame();
        $enemyShipsCollection = new CoordsCollection($game->getOtherShips());
        $shotCoord = $shotEvent->getValue();

        if ($enemyShipsCollection->contains($shotCoord)) {
            $attackerShots = $this->getAttackerShots($shotEvent);
            $result = $this->isSunk($shotCoord, $enemyShipsCollection, $attackerShots)
                ? self::SHOT_RESULT_SUNK
                : self::SHOT_RESULT_HIT;
        } else {
            $result = self::SHOT_RESULT_MISS;
        }

        return $result;
    }

    /**
     * Checks if the shot sinks the ship (if all other masts have been hit)
     *
     * @param string $mast
     * @param CoordsCollection $allShips
     * @param CoordsCollection $allShots
     * @return bool
     */
    public function isSunk($mast, CoordsCollection $allShips, CoordsCollection $allShots)
    {
        $sidePositions = $this->coordsManager->getByOffsets(
            $mast,
            [CoordsManager::OFFSET_TOP, CoordsManager::OFFSET_BOTTOM, CoordsManager::OFFSET_RIGHT, CoordsManager::OFFSET_LEFT]
        );
        // try to find a mast which hasn't been hit
        foreach ($sidePositions as $offset => $sidePosition) {
            if ($sidePosition
                && $this->remainingMastInDirectionExists($sidePosition, $allShips, $allShots, $offset)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $mast
     * @param CoordsCollection $allShips
     * @param CoordsCollection $allShots
     * @param string $offset
     * @return bool
     */
    private function remainingMastInDirectionExists($mast, CoordsCollection $allShips, CoordsCollection $allShots, $offset)
    {
        while ($allShips->contains($mast)) {
            if (!$allShots->contains($mast)) {
                return true;
            }
            $mast = $this->coordsManager->getByOffset($mast, $offset);
        }

        return false;
    }

    /**
     * @param Event $event
     * @return CoordsCollection
     */
    private function getAttackerShots(Event $event)
    {
        $shotEvents = $this->eventRepository->findForGameByTypeAndPlayer(
            $event->getGame(),
            Event::TYPE_SHOT,
            $event->getPlayer()
        );

        $attackerShots = new CoordsCollection();
        foreach ($shotEvents as $shotEvent) {
            $attackerShots->append($shotEvent->getValue());
        }

        return $attackerShots;
    }
}
