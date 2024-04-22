<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use ReflectionMethod;

interface RestRequestOptionsBuilderInterface
{
    public function buildOptions(array $attributes, ReflectionMethod $reflectionMethod): RestRequestOptions;
}
