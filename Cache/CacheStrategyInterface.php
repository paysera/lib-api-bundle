<?php

namespace Paysera\Bundle\RestBundle\Cache;

interface CacheStrategyInterface extends ModificationDateProviderInterface
{
    /**
     * @return int|null
     */
    public function getMaxAge();
}
