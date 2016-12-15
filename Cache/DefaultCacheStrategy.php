<?php

namespace Paysera\Bundle\RestBundle\Cache;

class DefaultCacheStrategy implements CacheStrategyInterface
{
    protected $maxAge;
    protected $provider;

    public function __construct($maxAge = 0, ModificationDateProviderInterface $provider = null)
    {
        $this->maxAge = $maxAge;
        $this->provider = $provider;
    }

    /**
     * @return int|null
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }

    /**
     * @param mixed $result Returned from controller, not normalizer
     *
     * @return \DateTime|null
     */
    public function getModifiedAt($result)
    {
        return $this->provider !== null ? $this->provider->getModifiedAt($result) : null;
    }
}
