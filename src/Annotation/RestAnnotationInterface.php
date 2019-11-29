<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Service\Annotation\ReflectionMethodWrapper;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;

interface RestAnnotationInterface
{
    public function isSeveralSupported(): bool;

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod);
}
