<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\Annotation\ReflectionMethodWrapper;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Body implements RestAnnotationInterface
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * @var string|null
     */
    private $denormalizationType;

    /**
     * @var string|null
     */
    private $denormalizationGroup;

    /**
     * @var bool|null
     */
    private $optional;

    public function __construct(array $options)
    {
        $this->setParameterName($options['parameterName']);
        $this->setDenormalizationType($options['denormalizationType'] ?? null);
        $this->setDenormalizationGroup($options['denormalizationGroup'] ?? null);
        $this->setOptional($options['optional'] ?? null);
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
     * @param string|null $denormalizationGroup
     * @return $this
     */
    public function setDenormalizationGroup($denormalizationGroup): self
    {
        $this->denormalizationGroup = $denormalizationGroup;
        return $this;
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
     * @param bool|null $optional
     * @return $this
     */
    private function setOptional($optional): self
    {
        $this->optional = $optional;
        return $this;
    }

    public function isSeveralSupported(): bool
    {
        return false;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod)
    {
        $options->setBodyParameterName($this->parameterName);
        $options->setBodyDenormalizationType($this->resolveDenormalizationType($reflectionMethod));
        $options->setBodyDenormalizationGroup($this->denormalizationGroup);
        $options->setBodyOptional($this->resolveIfBodyIsOptional($reflectionMethod));
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

        return $typeName;
    }

    private function resolveIfBodyIsOptional(ReflectionMethodWrapper $reflectionMethod): bool
    {
        if ($this->optional !== null) {
            return $this->optional;
        }

        return $reflectionMethod->getParameterByName($this->parameterName)->isDefaultValueAvailable();
    }
}
