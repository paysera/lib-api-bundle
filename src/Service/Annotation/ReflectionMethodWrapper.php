<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\Annotation;

use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper as NewReflectionMethodWrapper;

class_exists(NewReflectionMethodWrapper::class);

if (false) {
    /**
     * @internal
     * @deprecated since 1.8, to be removed in 2.0, use {@link NewReflectionMethodWrapper} instead
     */
    class ReflectionMethodWrapper extends NewReflectionMethodWrapper
    {
    }
}
