<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Attribute\Validation as ValidationAttribute;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Validation extends ValidationAttribute implements RestAnnotationInterface
{
    public function isSeveralSupported(): bool
    {
        return true;
    }
}
