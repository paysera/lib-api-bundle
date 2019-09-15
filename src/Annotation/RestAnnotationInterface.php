<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Annotation;

use Paysera\Bundle\RestBundle\Service\Annotation\ReflectionMethodWrapper;
use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;

interface RestAnnotationInterface
{
    public function isSeveralSupported(): bool;

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod);
}
