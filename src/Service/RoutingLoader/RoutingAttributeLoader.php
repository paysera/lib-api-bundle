<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use Paysera\Bundle\ApiBundle\Attribute\RestAttributeInterface;
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

    protected function configureRoute(
        Route $route,
        ReflectionClass $class,
        ReflectionMethod $method,
        object $annot
    ): void {
        parent::configureRoute($route, $class, $method, $annot);

        $this->loadAnnotations($route, $class, $method);
        $this->loadAttributes($route, $class, $method);
    }

    private function loadAnnotations(Route $route, ReflectionClass $class, ReflectionMethod $method): void
    {
        $annotations = [];
        foreach ($this->reader->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof RestAnnotationInterface) {
                $annotations[] = $annotation;
            }
        }

        foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof RestAnnotationInterface) {
                $annotations[] = $annotation;
            }
        }

        if ($annotations === []) {
            return;
        }

        $this->restRequestHelper->setOptionsForRoute(
            $route,
            $this->annotationOptionsBuilder->buildOptions($annotations, $method)
        );
    }

    private function loadAttributes(Route $route, ReflectionClass $class, ReflectionMethod $method): void
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

        if ($restAttributes === []) {
            return;
        }

        $this->restRequestHelper->setOptionsForRoute(
            $route,
            $this->attributeOptionsBuilder->buildOptions($restAttributes, $method)
        );
    }
}
