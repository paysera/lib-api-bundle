<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service\Annotation;

use Paysera\Bundle\RestBundle\Exception\ConfigurationException;
use ReflectionMethod;
use ReflectionParameter;

/**
 * @internal
 */
class ReflectionMethodWrapper
{
    private $reflectionMethod;

    public function __construct(ReflectionMethod $reflectionMethod)
    {
        $this->reflectionMethod = $reflectionMethod;
    }

    public function getParameterByName(string $name): ReflectionParameter
    {
        foreach ($this->reflectionMethod->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }

        throw new ConfigurationException(sprintf(
            'Parameter %s is configured but not found in method %s',
            '$' . $name,
            $this->getFriendlyName()
        ));
    }

    public function getNonBuiltInTypeForParameter(string $name): string
    {
        $parameter = $this->getParameterByName($name);

        $type = $parameter->getType();
        if ($type === null || $type->isBuiltin()) {
            throw new ConfigurationException(sprintf(
                'Expected non built-in type-hint for %s in %s',
                '$' . $parameter->getName(),
                $this->getFriendlyName()
            ));
        }

        return $type->getName();
    }

    public function getFriendlyName()
    {
        return sprintf(
            '%s::%s',
            $this->reflectionMethod->getDeclaringClass()->getName(),
            $this->reflectionMethod->getName()
        );
    }
}
