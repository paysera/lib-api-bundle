<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Annotation;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Exception\ConfigurationException;
use Paysera\Bundle\RestBundle\Service\Annotation\ReflectionMethodWrapper;
use ReflectionParameter;

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
     * @var bool|null
     */
    private $optional;

    public function __construct(array $options)
    {
        $this->setParameterName($options['parameterName']);
        $this->setDenormalizationType($options['denormalizationType'] ?? null);
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
        $parameter = $reflectionMethod->getParameterByName($this->parameterName);
        $options->setBodyDenormalizationType($this->resolveDenormalizationType($parameter));
        $options->setBodyOptional($this->resolveIfBodyIsOptional($parameter));
    }

    private function resolveDenormalizationType(ReflectionParameter $parameter): string
    {
        if ($this->denormalizationType !== null) {
            return $this->denormalizationType;
        }

        $type = $parameter->getType();
        if ($type === null || $type->isBuiltin()) {
            throw new ConfigurationException(sprintf(
                'Denormalization type could not be guessed for %s in %s::%s',
                '$' . $parameter->getName(),
                $parameter->getDeclaringClass()->getName(),
                $parameter->getDeclaringFunction()->getName()
            ));
        }

        return $type->getName();
    }

    private function resolveIfBodyIsOptional(ReflectionParameter $parameter): bool
    {
        if ($this->optional !== null) {
            return $this->optional;
        }

        return $parameter->isDefaultValueAvailable();
    }
}
