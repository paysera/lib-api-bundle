<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Attribute\BodyContentType as BodyContentTypeAttribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class BodyContentType extends BodyContentTypeAttribute implements RestAnnotationInterface
{
    public function isSeveralSupported(): bool
    {
        return false;
    }
}
