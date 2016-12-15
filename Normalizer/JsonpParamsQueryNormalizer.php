<?php

namespace Paysera\Bundle\RestBundle\Normalizer;

use Paysera\Bundle\RestBundle\Entity\JsonpParams;
use Paysera\Component\Serializer\Exception\InvalidDataException;
use Paysera\Component\Serializer\Normalizer\DenormalizerInterface;

class JsonpParamsQueryNormalizer implements DenormalizerInterface
{
    /**
     * Maps raw data to some structure. Usually array to entity object
     *
     * @param mixed $data
     *
     * @return mixed
     *
     * @throws InvalidDataException
     */
    public function mapToEntity($data)
    {
        $params = new JsonpParams();
        if (isset($data['callback']) && is_string($data['callback'])) {
            $params->setCallback($data['callback']);
        }
        if (isset($data['parameter']) && is_string($data['parameter'])) {
            $params->setParameter($data['parameter']);
        }
        return $params;
    }
} 
