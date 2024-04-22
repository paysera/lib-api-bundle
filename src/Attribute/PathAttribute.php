<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Entity\PathAttributeResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PathAttribute implements RestAttributeInterface
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * @var string
     */
    private $pathPartName;

    /**
     * @var string|null
     */
    private $resolverType;

    /**
     * @var bool|null
     */
    private $resolutionMandatory;

    public function __construct(
        array $data = [],
        string $parameterName = null,
        string $pathPartName = null,
        ?string $resolverType = null,
        ?bool $resolutionMandatory = null
    ) {
        $this->setParameterName($data['parameterName'] ?? $parameterName);
        $this->setPathPartName($data['pathPartName'] ?? $pathPartName);
        $this->setResolverType($data['resolverType'] ?? $resolverType);
        $this->setResolutionMandatory($data['resolutionMandatory'] ?? $resolutionMandatory);
    }

    private function setParameterName(string $parameterName): self
    {
        $this->parameterName = $parameterName;
        return $this;
    }

    private function setPathPartName(string $pathPartName): self
    {
        $this->pathPartName = $pathPartName;
        return $this;
    }

    private function setResolverType(?string $resolverType): self
    {
        $this->resolverType = $resolverType;
        return $this;
    }

    private function setResolutionMandatory(?bool $resolutionMandatory): self
    {
        $this->resolutionMandatory = $resolutionMandatory;
        return $this;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod): void
    {
        $options->addPathAttributeResolverOptions(
            (new PathAttributeResolverOptions())
                ->setParameterName($this->parameterName)
                ->setPathPartName($this->pathPartName)
                ->setPathAttributeResolverType($this->resolvePathAttributeResolverType($reflectionMethod))
                ->setResolutionMandatory($this->resolveIfResolutionIsMandatory($reflectionMethod))
        );
    }

    private function resolvePathAttributeResolverType(ReflectionMethodWrapper $reflectionMethod): string
    {
        if ($this->resolverType !== null) {
            return $this->resolverType;
        }

        try {
            return $reflectionMethod->getNonBuiltInTypeForParameter($this->parameterName);
        } catch (ConfigurationException $exception) {
            throw new ConfigurationException(
                sprintf(
                    'Denormalization type could not be guessed for %s in %s',
                    '$' . $this->parameterName,
                    $reflectionMethod->getFriendlyName()
                )
            );
        }
    }

    private function resolveIfResolutionIsMandatory(ReflectionMethodWrapper $reflectionMethod): bool
    {
        if ($this->resolutionMandatory !== null) {
            return $this->resolutionMandatory;
        }

        $parameter = $reflectionMethod->getParameterByName($this->parameterName);

        return !$parameter->isDefaultValueAvailable();
    }
}
