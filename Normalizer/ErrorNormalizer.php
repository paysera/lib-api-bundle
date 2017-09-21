<?php

namespace Paysera\Bundle\RestBundle\Normalizer;

use Paysera\Component\Serializer\Normalizer\DenormalizerInterface;
use Paysera\Component\Serializer\Normalizer\NormalizerInterface;
use Paysera\Component\Serializer\Normalizer\BaseDenormalizer;
use Paysera\Bundle\RestBundle\Entity\Error;

class ErrorNormalizer extends BaseDenormalizer implements NormalizerInterface
{
    private $violationsDenormalizer;
    private $violationsNormalizer;

    public function __construct(
        DenormalizerInterface $violationsDenormalizer,
        NormalizerInterface $violationsNormalizer
    ) {
        $this->violationsDenormalizer = $violationsDenormalizer;
        $this->violationsNormalizer = $violationsNormalizer;
    }

    /**
     * @param array $data
     *
     * @return Error
     */
    public function mapToEntity($data)
    {
        $error = new Error();

        if (isset($data['error'])) {
            $error->setCode($data['error']);
        }

        if (isset($data['error_description'])) {
            $error->setMessage($data['error_description']);
        }

        if (isset($data['error_uri'])) {
            $error->setUri($data['error_uri']);
        }

        if (isset($data['error_properties'])) {
            $error->setProperties($data['error_properties']);
        }

        if (isset($data['error_data'])) {
            $error->setData($data['error_data']);
        }

        if (isset($data['errors'])) {
            $error->setViolations($this->violationsDenormalizer->mapToEntity($data['errors']));
        }

        return $error;
    }

    /**
     * @param Error $entity
     *
     * @return array
     */
    public function mapFromEntity($entity)
    {
        $data = [];

        $data['error'] = $entity->getCode();

        if ($entity->getMessage() !== null) {
            $data['error_description'] = $entity->getMessage();
        }

        if ($entity->getUri() !== null) {
            $data['error_uri'] = $entity->getUri();
        }

        if ($entity->getProperties() !== null) {
            $data['error_properties'] = $entity->getProperties();
        }

        if ($entity->getData() !== null) {
            $data['error_data'] = $entity->getData();
        }

        if (count($entity->getViolations()) > 0) {
            $data['errors'] = $this->violationsNormalizer->mapFromEntity($entity->getViolations());
        }

        return $data;
    }
}
