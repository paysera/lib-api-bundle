<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

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

    /**
     * @throws ConfigurationException on Symfony 7 and later when the controller uses the bundle's docblock annotations
     */
    private function loadAnnotations(Route $route, ReflectionClass $class, ReflectionMethod $method): void
    {
        if (!property_exists($this, 'reader')) {
            $this->refuseDocblockAnnotations($class, $method);
            return;
        }

        if (!isset($this->reader)) {
            return;
        }

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

    /**
     * Symfony 7 gives the route loader no annotation reader, so these options would be ignored without a word — an
     * endpoint would lose its required permissions. Fail at route loading instead and name the attributes to use.
     *
     * @throws ConfigurationException
     */
    private function refuseDocblockAnnotations(ReflectionClass $class, ReflectionMethod $method): void
    {
        $annotations = (new DocblockAnnotationFinder())->findBundleAnnotations($class, $method);
        if ($annotations === []) {
            return;
        }

        $attributes = [];
        foreach ($annotations as $annotation) {
            $attributes[] = sprintf(
                '#[%s]',
                str_replace('\\Annotation\\', '\\Attribute\\', $annotation)
            );
        }

        throw new ConfigurationException(sprintf(
            '%s::%s() configures its REST endpoint with docblock annotations (%s), which Symfony 7 does not read, '
            . 'so the endpoint would run without those options. Use the attributes instead: %s.',
            $class->getName(),
            $method->getName(),
            implode(', ', $annotations),
            implode(', ', $attributes)
        ));
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
