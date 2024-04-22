<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Attribute\Body as BodyAttribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Body extends BodyAttribute implements RestAnnotationInterface
{
    public function isSeveralSupported(): bool
    {
        return false;
    }
}
