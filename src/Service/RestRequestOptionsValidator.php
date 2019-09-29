<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service;

use InvalidArgumentException;
use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Exception\ConfigurationException;
use Paysera\Bundle\RestBundle\Service\PathAttributeResolver\PathAttributeResolverRegistry;
use Paysera\Component\Normalization\NormalizerRegistryInterface;

class RestRequestOptionsValidator
{
    private $normalizerRegistry;
    private $pathAttributeResolverRegistry;

    /**
     * @param NormalizerRegistryInterface $normalizerRegistry
     * @param PathAttributeResolverRegistry $pathAttributeResolverRegistry
     * @internal
     */
    public function __construct(
        NormalizerRegistryInterface $normalizerRegistry,
        PathAttributeResolverRegistry $pathAttributeResolverRegistry
    ) {
        $this->normalizerRegistry = $normalizerRegistry;
        $this->pathAttributeResolverRegistry = $pathAttributeResolverRegistry;
    }

    public function validateRestRequestOptions(RestRequestOptions $options, string $methodName)
    {
        $normalizer = $options->getResponseNormalizationType();
        if ($normalizer !== null && !$this->normalizerRegistry->hasNormalizer($normalizer)) {
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
            $this->validatePathAttributeResolver(
                $pathAttributeResolverOptions->getPathAttributeResolverType(),
                $methodName
            );
        }
    }

    private function validateDenormalizer(string $type, string $methodName)
    {
        $denormalizerType = $this->normalizerRegistry->getDenormalizerType($type);
        if ($denormalizerType === NormalizerRegistryInterface::DENORMALIZER_TYPE_NONE) {
            throw new ConfigurationException(sprintf(
                'Denormalizer "%s" does not exist (configured for "%s")',
                $type,
                $methodName
            ));
        }
    }

    private function validatePathAttributeResolver(string $type, string $methodName)
    {
        try {
            $this->pathAttributeResolverRegistry->getResolverByType($type);
        } catch (InvalidArgumentException $exception) {
            throw new ConfigurationException(sprintf(
                'Path attribute resolver "%s" does not exist (configured for "%s")',
                $type,
                $methodName
            ));
        }
    }
}
