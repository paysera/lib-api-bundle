<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Normalizer;

use Paysera\Bundle\RestBundle\Entity\Error;
use Paysera\Component\Normalization\NormalizationContext;
use Paysera\Component\Normalization\NormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;

class ErrorNormalizer implements NormalizerInterface, TypeAwareInterface
{
    /**
     * @param Error $result
     * @param NormalizationContext $normalizationContext
     * @return array
     */
    public function normalize($result, NormalizationContext $normalizationContext)
    {
        return [
            'error' => $result->getCode(),
            'error_description' => $result->getMessage(),
            'error_uri' => $result->getUri(),
            'error_properties' => $result->getProperties(),
            'error_data' => $result->getData(),
            'errors' => $result->getViolations() !== [] ? $result->getViolations() : null,
        ];
    }

    public function getType(): string
    {
        return Error::class;
    }
}
