<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Entity\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Exception\GameFlowException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class OnlyBeforeStartValidator extends ConstraintValidator
{
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
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof OnlyBeforeStart) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\OnlyBeforeStart', __NAMESPACE__));
        }

        $root = $this->context->getRoot();
        if (!$root instanceof Game) {
            return;
        }

        if ($this->hasGameAlreadyStarted($root)) {
            throw new GameFlowException('Ships can\'t be changed - game has already started');
        }
    }

    /**
     * @param Game $game
     * @return bool
     */
    protected function hasGameAlreadyStarted(Game $game)
    {
        $events = $this->eventRepository->findForGameByTypeAndPlayer($game, Event::TYPE_START_GAME, $game->getPlayerNumber());

        return !$events->isEmpty();
    }
}
