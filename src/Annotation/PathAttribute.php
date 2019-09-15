<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Annotation;

use Paysera\Bundle\RestBundle\Entity\PathAttributeResolverOptions;
use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Exception\ConfigurationException;
use Paysera\Bundle\RestBundle\Service\Annotation\ReflectionMethodWrapper;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class PathAttribute implements RestAnnotationInterface
{
    const DENORMALIZATION_TYPE_POSTFIX = ':find';

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
    private $denormalizationType;

    /**
     * @var bool|null
     */
    private $resolutionMandatory;

    public function __construct(array $options)
    {
        $this->setParameterName($options['parameterName']);
        $this->setPathPartName($options['pathPartName']);
        $this->setDenormalizationType($options['denormalizationType'] ?? null);
        $this->setResolutionMandatory($options['resolutionMandatory'] ?? null);
    }

    /**
     * @param string $parameterName
     * @return $this
     */
    private function setParameterName(string $parameterName): self
    {
        $this->parameterName = $parameterName;
        return $this;
    }

    /**
     * @param string $pathPartName
     * @return $this
     */
    private function setPathPartName(string $pathPartName): self
    {
        $this->pathPartName = $pathPartName;
        return $this;
    }

    /**
     * @param string|null $denormalizationType
     * @return $this
     */
    private function setDenormalizationType($denormalizationType): self
    {
        $this->denormalizationType = $denormalizationType;
        return $this;
    }

    /**
     * @param bool|null $resolutionMandatory
     * @return $this
     */
    private function setResolutionMandatory($resolutionMandatory): self
    {
        $this->resolutionMandatory = $resolutionMandatory;
        return $this;
    }

    public function isSeveralSupported(): bool
    {
        return true;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod)
    {
        $options->addPathAttributeResolverOptions(
            (new PathAttributeResolverOptions())
                ->setParameterName($this->parameterName)
                ->setPathPartName($this->pathPartName)
                ->setDenormalizationType($this->resolveDenormalizationType($reflectionMethod))
                ->setResolutionMandatory($this->resolveIfResolutionIsMandatory($reflectionMethod))
        );
    }

    private function resolveDenormalizationType(ReflectionMethodWrapper $reflectionMethod): string
    {
        if ($this->denormalizationType !== null) {
            return $this->denormalizationType;
        }

        try {
            $typeName = $reflectionMethod->getNonBuiltInTypeForParameter($this->parameterName);
        } catch (ConfigurationException $exception) {
            throw new ConfigurationException(sprintf(
                'Denormalization type could not be guessed for %s in %s',
                '$' . $this->parameterName,
                $reflectionMethod->getFriendlyName()
            ));
        }

        return sprintf('%s%s', $typeName, self::DENORMALIZATION_TYPE_POSTFIX);
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
