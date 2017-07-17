<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Repository\EventRepository;
use AppBundle\Entity\Game;
use AppBundle\Exception\DuplicatedEventTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class UniqueEventValidator extends ConstraintValidator
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
        if (!$constraint instanceof UniqueEvent) {
            throw new UnexpectedTypeException($constraint, UniqueEvent::class);
        }

        $root = $this->context->getRoot();
        if (!$root instanceof Event) {
            throw new UnexpectedTypeException($root, Event::class);
        }

        if (!in_array($value, $constraint->uniqueEvents)) {
            return;
        }

        if ($this->eventAlreadyExists($root->getGame(), $value)) {
            throw new DuplicatedEventTypeException($value);
        }
    }

    /**
     * @param Game $game
     * @param string $eventType
     * @return bool
     */
    protected function eventAlreadyExists(Game $game, $eventType)
    {
        $events = $this->eventRepository->findForGameByTypeAndPlayer($game, $eventType, $game->getPlayerNumber());

        return !$events->isEmpty();
    }
}
