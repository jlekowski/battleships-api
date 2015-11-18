<?php

namespace AppBundle\Battle;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Exception\InvalidCoordinatesException;
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
     * @param EventRepository $eventRepository
     */
    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * @todo maybe pass $shot and $otherShips, but then how to get shots?
     * @param Event $shotEvent
     * @return string miss|hit|sunk
     * @throws UnexpectedEventTypeException
     * @throws InvalidCoordinatesException
     */
    public function getShotResult(Event $shotEvent)
    {
        if ($shotEvent->getType() !== Event::TYPE_SHOT) {
            throw new UnexpectedEventTypeException($shotEvent->getType(), Event::TYPE_SHOT);
        }

        $game = $shotEvent->getGame();
        $enemyShips = new CoordsInfoCollection($game->getOtherShips());
        $shot = new CoordsInfo($shotEvent->getValue());

        if ($enemyShips->contains($shot)) {
            // @todo is there a nicer way to do it (getPlayerShots()), and do I need getEvents() method?
            $attackerShots = $this->getAttackerShots($shotEvent);
            $result = $this->isSunk($shot, $enemyShips, $attackerShots)
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
     * @param CoordsInfo $mast
     * @param CoordsInfoCollection $allShips
     * @param CoordsInfoCollection $allShots
     * @return bool
     * @throws InvalidCoordinatesException
     */
    public function isSunk(CoordsInfo $mast, CoordsInfoCollection $allShips, CoordsInfoCollection $allShots)
    {
        $sidePositions = $mast->getSidePositions();
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
     * @param CoordsInfo $mast
     * @param CoordsInfoCollection $allShips
     * @param CoordsInfoCollection $allShots
     * @param string $offset
     * @return bool
     */
    private function remainingMastInDirectionExists(
        CoordsInfo $mast,
        CoordsInfoCollection $allShips,
        CoordsInfoCollection $allShots,
        $offset
    ) {
        while ($allShips->contains($mast)) {
            if (!$allShots->contains($mast)) {
                return true;
            }
            $mast = $mast->getOffsetCoords($offset);
        }

        return false;
    }

    /**
     * @param Event $event
     * @return CoordsInfoCollection
     */
    private function getAttackerShots(Event $event)
    {
        $shotEvents = $this->eventRepository->findForGameByTypeAndPlayer(
            $event->getGame(),
            Event::TYPE_SHOT,
            $event->getPlayer()
        );

        $attackerShots = [];
        foreach ($shotEvents as $shotEvent) {
            // @todo DRY - shot event value (see IsAllowedToShootValidator)
            $attackerShots[] = explode('|', $shotEvent->getValue())[0];
        }

        return new CoordsInfoCollection($attackerShots);
    }
}
