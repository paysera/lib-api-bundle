<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;

interface RestAnnotationInterface
{
    public function isSeveralSupported(): bool;

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod): void;
}
