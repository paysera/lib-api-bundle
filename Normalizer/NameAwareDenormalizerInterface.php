<?php

namespace Paysera\Bundle\RestBundle\Normalizer;

use Paysera\Component\Serializer\Normalizer\DenormalizerInterface;

interface NameAwareDenormalizerInterface extends DenormalizerInterface
{
    /**
     * Returns name for denormalizer entity
     *
     * @return string
     */
    public function getName();
}
