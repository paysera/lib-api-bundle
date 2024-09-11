<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions as RequiredPermissionsAttribute;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class RequiredPermissions extends RequiredPermissionsAttribute implements RestAnnotationInterface
{
    public function isSeveralSupported(): bool
    {
        return true;
    }
}
