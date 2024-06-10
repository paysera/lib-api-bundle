<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;

interface RestAttributeInterface
{
    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod);
}
