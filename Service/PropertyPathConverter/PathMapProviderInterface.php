<?php

namespace Paysera\Bundle\RestBundle\Service\PropertyPathConverter;

interface PathMapProviderInterface
{
    /**
     * @return array
     */
    public function getPathMap();
}
