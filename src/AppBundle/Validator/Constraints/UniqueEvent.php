<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueEvent extends Constraint
{
    public $uniqueEvents = [];

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'uniqueEvents';
    }

    /**
     * @inheritdoc
     */
    public function validatedBy()
    {
        return 'unique_event';
    }
}
