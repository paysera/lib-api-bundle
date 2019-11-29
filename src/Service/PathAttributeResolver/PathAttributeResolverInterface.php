<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\PathAttributeResolver;

interface PathAttributeResolverInterface
{
    /**
     * @param mixed $attributeValue
     * @return mixed|null
     */
    public function resolveFromAttribute($attributeValue);
}
