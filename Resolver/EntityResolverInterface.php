<?php

namespace Paysera\Bundle\RestBundle\Resolver;

interface EntityResolverInterface
{
    /**
     * @param string $value
     * @return object
     */
    public function resolveFrom($value);
}
