<?php

namespace AppBundle\Validator\Constraints;

use AppBundle\Battle\CoordsManager;
use AppBundle\Entity\Event;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @Annotation
 */
class EventValueValidator extends ConstraintValidator
{
    /**
     * @var CoordsManager
     */
    protected $coordsManager;

    /**
     * @param CoordsManager $coordsManager
     */
    public function __construct(CoordsManager $coordsManager)
    {
        $this->coordsManager = $coordsManager;
    }

    /**
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EventValue) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\EventValue', __NAMESPACE__));
        }

        if (!$value instanceof Event) {
            throw new UnexpectedTypeException($value, 'AppBundle\Entity\Event');
        }

        switch ($value->getType()) {
            case Event::TYPE_SHOT:
                $this->coordsManager->validateCoords($value->getValue());
                break;

            default:
                break;
        }
    }
}
