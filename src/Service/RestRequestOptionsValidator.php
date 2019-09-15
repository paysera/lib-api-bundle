<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Exception\ConfigurationException;
use Paysera\Component\Normalization\NormalizerRegistryInterface;

class RestRequestOptionsValidator
{
    private $registry;

    /**
     * @internal
     * @param NormalizerRegistryInterface $registry
     */
    public function __construct(NormalizerRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function validateRestRequestOptions(RestRequestOptions $options, string $methodName)
    {
        $normalizer = $options->getResponseNormalizationType();
        if ($normalizer !== null && !$this->registry->hasNormalizer($normalizer)) {
            throw new ConfigurationException(sprintf(
                'Normalizer %s does not exist (configured for %s)',
                $normalizer,
                $methodName
            ));
        }

        if ($options->hasBodyDenormalization()) {
            $this->validateDenormalizer($options->getBodyDenormalizationType(), $methodName);
        }

        foreach ($options->getQueryResolverOptionsList() as $queryResolverOptions) {
            $this->validateDenormalizer($queryResolverOptions->getDenormalizationType(), $methodName);
        }

        foreach ($options->getPathAttributeResolverOptionsList() as $pathAttributeResolverOptions) {
            $this->validateDenormalizer($pathAttributeResolverOptions->getDenormalizationType(), $methodName, true);
        }
    }

    private function validateDenormalizer(string $name, string $methodName, bool $requireMixed = false)
    {
        $type = $this->registry->getDenormalizerType($name);
        if ($type === NormalizerRegistryInterface::DENORMALIZER_TYPE_NONE) {
            throw new ConfigurationException(sprintf(
                'Denormalizer %s does not exist (configured for %s)',
                $name,
                $methodName
            ));
        }

        if (!$requireMixed) {
            return;
        }

        if ($type !== NormalizerRegistryInterface::DENORMALIZER_TYPE_MIXED) {
            throw new ConfigurationException(sprintf(
                'Denormalizer %s (configured for %s) type must be mixed, current type is %s',
                $name,
                $methodName,
                $type
            ));
        }
    }
}
