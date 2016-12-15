<?php

namespace Paysera\Bundle\RestBundle\Resolver;

class ServiceAwareAttributeResolver implements AttributeResolverInterface
{
    /**
     * @var EntityResolverInterface
     */
    private $resolver;
    /**
     * @var string
     */
    private $attributeName;
    /**
     * @var string
     */
    private $parameterName;

    public function __construct(EntityResolverInterface $resolver, $attributeName, $parameterName)
    {
        $this->resolver = $resolver;
        $this->attributeName = $attributeName;
        $this->parameterName = $parameterName;
    }

    public function resolveFromAttributeValue($attributeValue)
    {
        return $this->resolver->resolveFrom($attributeValue);
    }

    public function getParameterName()
    {
        return $this->parameterName;
    }

    public function getAttributeName()
    {
        return $this->attributeName;
    }
}
