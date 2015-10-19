<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OnlyBeforeStart extends Constraint
{
    /**
     * @inheritdoc
     */
    public function validatedBy()
    {
        return 'only_before_start_validator';
    }
}
