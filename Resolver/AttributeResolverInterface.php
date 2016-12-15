<?php

namespace Paysera\Bundle\RestBundle\Resolver;

interface AttributeResolverInterface
{
    /**
     * Extracts a an entity from the provided attribute
     *
     * @param string $attributeValue
     * @return mixed
     */
    public function resolveFromAttributeValue($attributeValue);

    /**
     * The name of the controller parameter,
     * that will be used to pass the resolved entity
     *
     * @return string
     */
    public function getParameterName();

    /**
     * The name of the request attribute that will be used in mapping
     *
     * @return string
     */
    public function getAttributeName();
}
