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
    private const ANNOTATION_NAMESPACE = 'Paysera\\Bundle\\ApiBundle\\Annotation\\';
    private const ATTRIBUTE_NAMESPACE = 'Paysera\\Bundle\\ApiBundle\\Attribute\\';

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
     * @var DocblockAnnotationFinder|null
     */
    private $docblockAnnotationFinder;

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
        if ($this->docblockAnnotationFinder === null) {
            $this->docblockAnnotationFinder = new DocblockAnnotationFinder();
        }
        $annotations = $this->docblockAnnotationFinder->findBundleAnnotations($class, $method);
        if ($annotations === []) {
            return;
        }

        $replacements = [];
        foreach ($annotations as $annotation) {
            $replacements[] = strpos($annotation, self::ANNOTATION_NAMESPACE) === 0
                ? '#[\\' . self::ATTRIBUTE_NAMESPACE . substr($annotation, strlen(self::ANNOTATION_NAMESPACE)) . ']'
                : sprintf(
                    'an attribute implementing \\%s in place of \\%s',
                    RestAttributeInterface::class,
                    $annotation
                );
        }

        throw new ConfigurationException(sprintf(
            '%s::%s() uses docblock annotations of paysera/lib-api-bundle (\\%s). Symfony 7 does not read docblock '
            . 'annotations, so they would have no effect. Use the PHP attributes instead: %s.',
            $class->getName(),
            $method->getName(),
            implode(', \\', $annotations),
            implode(', ', $replacements)
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
