<?php

namespace Paysera\Bundle\RestBundle\Cache;

interface ModificationDateProviderInterface
{
    /**
     * @param mixed $result Returned from controller, not normalizer
     *
     * @return \DateTime|null
     */
    public function getModifiedAt($result);
}
