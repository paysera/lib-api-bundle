<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Attribute\Query as QueryAttribute;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Query extends QueryAttribute implements RestAnnotationInterface
{
    public function isSeveralSupported(): bool
    {
        return true;
    }
}
