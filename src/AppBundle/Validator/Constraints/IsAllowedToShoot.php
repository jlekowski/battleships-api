<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsAllowedToShoot extends Constraint
{
    /**
     * @inheritdoc
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @inheritdoc
     */
    public function validatedBy()
    {
        return 'is_allowed_to_shoot';
    }
}
