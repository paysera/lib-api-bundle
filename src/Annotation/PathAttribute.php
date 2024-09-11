<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Attribute\PathAttribute as PathAttributeAttribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class PathAttribute extends PathAttributeAttribute implements RestAnnotationInterface
{
    public function isSeveralSupported(): bool
    {
        return true;
    }
}
