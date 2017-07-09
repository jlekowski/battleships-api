<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueEvent extends Constraint
{
    /**
     * @var array
     */
    public $uniqueEvents = [];

    /**
     * @inheritdoc
     */
    public function getDefaultOption()
    {
        return 'uniqueEvents';
    }
}
