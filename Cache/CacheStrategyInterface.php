<?php

namespace Paysera\Bundle\RestBundle\Cache;

interface CacheStrategyInterface extends ModificationDateProviderInterface
{
    const NO_CACHE = 'no_cache';

    /**
     * @return int|null
     */
    public function getMaxAge();
}
