<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service\PathAttributeResolver;

class PathAttributeResolutionManager
{
    private $registry;

    public function __construct(PathAttributeResolverRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param mixed $attributeValue
     * @param string $type
     * @return mixed|null
     */
    public function resolvePathAttribute($attributeValue, string $type)
    {
        return $this->registry->getResolverByType($type)->resolveFromAttribute($attributeValue);
    }
}
