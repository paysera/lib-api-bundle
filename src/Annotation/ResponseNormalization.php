<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Attribute\ResponseNormalization as ResponseNormalizationAttribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class ResponseNormalization extends ResponseNormalizationAttribute implements RestAnnotationInterface
{
    public function isSeveralSupported(): bool
    {
        return false;
    }
}
