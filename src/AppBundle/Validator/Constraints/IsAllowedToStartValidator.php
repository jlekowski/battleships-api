<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Entity\Event;
use AppBundle\Exception\GameFlowException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class IsAllowedToStartValidator extends ConstraintValidator
{
    /**
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsAllowedToStart) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\IsAllowedToShoot', __NAMESPACE__));
        }

        if (!$value instanceof Event) {
            throw new UnexpectedTypeException($value, 'AppBundle\Entity\Event');
        }

        if ($value->getType() !== Event::TYPE_START_GAME) {
            return;
        }

        if (!$value->getGame()->getPlayerShips()) {
            throw new GameFlowException('You must set ships first');
        }
    }
}
