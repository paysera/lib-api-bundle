<?php

namespace Paysera\Bundle\RestBundle\Normalizer;

use Paysera\Component\Serializer\Exception\InvalidDataException;
use Paysera\Component\Serializer\Normalizer\DenormalizerInterface;

class NameAwareDenormalizer implements NameAwareDenormalizerInterface
{
    protected $denormalizer;
    protected $name;

    public function __construct(DenormalizerInterface $denormalizer, $name)
    {
        $this->denormalizer = $denormalizer;
        $this->name = $name;
    }

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
        return $this->denormalizer->mapToEntity($data);
    }

    /**
     * Returns name for denormalizer entity
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
