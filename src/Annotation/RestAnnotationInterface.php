<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

interface RestAnnotationInterface
{
    public function isSeveralSupported(): bool;
}
