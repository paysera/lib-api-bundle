<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use Paysera\Bundle\ApiBundle\Attribute\RestAttributeInterface;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Route;

/**
 * @internal
 */
class RoutingAttributeLoader extends AttributeRouteControllerLoader
{
    /**
     * @var RestRequestHelper
     */
    private $restRequestHelper;

    /**
     * @var RestRequestAnnotationOptionsBuilder
     */
    private $annotationOptionsBuilder;

    /**
     * @var RestRequestAttributeOptionsBuilder
     */
    private $attributeOptionsBuilder;

    /**
     * @var bool
     */
    private $docblockAnnotationsEnabled = false;

    /**
     * @var AnnotationReader|null
     */
    private $annotationReader;

    public function setRequestHelper(RestRequestHelper $restRequestHelper)
    {
        $this->restRequestHelper = $restRequestHelper;
    }

    public function setRestRequestAnnotationOptionsBuilder(RestRequestAnnotationOptionsBuilder $annotationOptionsBuilder
    ) {
        $this->annotationOptionsBuilder = $annotationOptionsBuilder;
    }

    public function setRestRequestAttributeOptionsBuilder(RestRequestAttributeOptionsBuilder $attributeOptionsBuilder)
    {
        $this->attributeOptionsBuilder = $attributeOptionsBuilder;
    }

    public function setDocblockAnnotationsEnabled(bool $docblockAnnotationsEnabled)
    {
        $this->docblockAnnotationsEnabled = $docblockAnnotationsEnabled;
    }

    protected function configureRoute(
        Route $route,
        ReflectionClass $class,
        ReflectionMethod $method,
        object $annot
    ): void {
        parent::configureRoute($route, $class, $method, $annot);

        $annotations = $this->getRestAnnotations($class, $method);
        $attributes = $this->getRestAttributes($class, $method);
        if ($annotations !== [] && $attributes !== []) {
            throw new ConfigurationException(sprintf(
                'The route of method %s::%s() is configured with both docblock annotations and attributes of the'
                . ' bundle, on the method or its class: use one of them',
                $class->getName(),
                $method->getName()
            ));
        }

        if ($annotations !== []) {
            $this->restRequestHelper->setOptionsForRoute(
                $route,
                $this->annotationOptionsBuilder->buildOptions($annotations, $method)
            );
        } elseif ($attributes !== []) {
            $this->restRequestHelper->setOptionsForRoute(
                $route,
                $this->attributeOptionsBuilder->buildOptions($attributes, $method)
            );
        }
    }

    /**
     * @return RestAnnotationInterface[]
     */
    private function getRestAnnotations(ReflectionClass $class, ReflectionMethod $method): array
    {
        $reader = $this->getAnnotationReader();
        if ($reader === null) {
            return [];
        }

        $annotations = [];
        foreach ($reader->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof RestAnnotationInterface) {
                $annotations[] = $annotation;
            }
        }

        foreach ($reader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof RestAnnotationInterface) {
                $annotations[] = $annotation;
            }
        }

        return $annotations;
    }

    private function getAnnotationReader(): ?Reader
    {
        if (isset($this->reader)) {
            return $this->reader;
        }

        if (!$this->docblockAnnotationsEnabled) {
            return null;
        }

        if ($this->annotationReader === null) {
            $this->annotationReader = new AnnotationReader();
        }

        return $this->annotationReader;
    }

    /**
     * @return RestAttributeInterface[]
     */
    private function getRestAttributes(ReflectionClass $class, ReflectionMethod $method): array
    {
        $attributes = array_merge($class->getAttributes(), $method->getAttributes());

        $restAttributes = [];
        foreach ($attributes as $attribute) {
            if (is_subclass_of($attribute->getName(), RestAttributeInterface::class)) {
                /** @var RestAttributeInterface $instance */
                $instance = $attribute->newInstance();
                $restAttributes[] = $instance;
            }
        }

        return $restAttributes;
    }
}
