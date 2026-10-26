<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Doctrine\Common\Annotations\Reader;
use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use Paysera\Bundle\ApiBundle\Attribute\Body;
use Paysera\Bundle\ApiBundle\Attribute\BodyContentType;
use Paysera\Bundle\ApiBundle\Attribute\PathAttribute;
use Paysera\Bundle\ApiBundle\Attribute\Query;
use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Attribute\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Attribute\RestAttributeInterface;
use Paysera\Bundle\ApiBundle\Attribute\Validation;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Routing\Route;
use Throwable;

/**
 * @internal
 */
class RestRouteConfigurator
{
    private const BUNDLE_NAMESPACE = 'Paysera\\Bundle\\ApiBundle\\';
    private const BUNDLE_ATTRIBUTES = [
        Body::class,
        BodyContentType::class,
        PathAttribute::class,
        Query::class,
        RequiredPermissions::class,
        ResponseNormalization::class,
        Validation::class,
    ];

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
     * @var DocblockAnnotationGuard
     */
    private $docblockAnnotationGuard;

    /**
     * @var array<string, object[]>
     */
    private $classAnnotations;

    public function __construct(
        RestRequestHelper $restRequestHelper,
        RestRequestAnnotationOptionsBuilder $annotationOptionsBuilder,
        RestRequestAttributeOptionsBuilder $attributeOptionsBuilder,
        DocblockAnnotationGuard $docblockAnnotationGuard
    ) {
        $this->restRequestHelper = $restRequestHelper;
        $this->annotationOptionsBuilder = $annotationOptionsBuilder;
        $this->attributeOptionsBuilder = $attributeOptionsBuilder;
        $this->docblockAnnotationGuard = $docblockAnnotationGuard;
        $this->classAnnotations = [];
    }

    public function configureRoute(
        Route $route,
        ReflectionClass $class,
        ReflectionMethod $method,
        ?Reader $reader
    ): void {
        $attributes = $this->getRestAttributes($class, $method);
        if ($reader === null) {
            $this->docblockAnnotationGuard->failOnDocblockAnnotations($class, $method);
            $annotations = [];
        } else {
            $annotations = $this->getRestAnnotations($class, $method, $reader);
        }

        if ($annotations !== [] && $attributes !== []) {
            throw new ConfigurationException(sprintf(
                '%s::%s() is configured with both docblock annotations and attributes of the bundle, use one of them',
                $class->getName(),
                $method->getName()
            ));
        }

        if ($annotations !== []) {
            $this->restRequestHelper->setOptionsForRoute(
                $route,
                $this->annotationOptionsBuilder->buildOptions($annotations, $method)
            );
        }

        if ($attributes !== []) {
            $this->restRequestHelper->setOptionsForRoute(
                $route,
                $this->attributeOptionsBuilder->buildOptions($attributes, $method)
            );
        }
    }

    /**
     * @return RestAnnotationInterface[]
     */
    private function getRestAnnotations(ReflectionClass $class, ReflectionMethod $method, Reader $reader): array
    {
        try {
            $annotations = array_merge(
                $this->getClassAnnotations($reader, $class),
                $reader->getMethodAnnotations($method)
            );
        } catch (Throwable $exception) {
            throw $this->createReadException('docblock annotations', $class, $method, $exception);
        }

        return array_values(array_filter($annotations, static function ($annotation): bool {
            return $annotation instanceof RestAnnotationInterface;
        }));
    }

    /**
     * @return object[]
     */
    private function getClassAnnotations(Reader $reader, ReflectionClass $class): array
    {
        $className = $class->getName();
        if (!isset($this->classAnnotations[$className])) {
            $this->classAnnotations[$className] = $reader->getClassAnnotations($class);
        }

        return $this->classAnnotations[$className];
    }

    /**
     * @return RestAttributeInterface[]
     */
    private function getRestAttributes(ReflectionClass $class, ReflectionMethod $method): array
    {
        if (PHP_VERSION_ID < 80000) {
            return [];
        }

        $attributes = array_merge(
            $class->getAttributes(RestAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF),
            $method->getAttributes(RestAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF)
        );
        $this->failOnUnknownBundleAttributes($class, $method);

        $restAttributes = [];
        try {
            foreach ($attributes as $attribute) {
                $restAttributes[] = $attribute->newInstance();
            }
        } catch (Throwable $exception) {
            throw $this->createReadException('attributes', $class, $method, $exception);
        }

        return $restAttributes;
    }

    private function failOnUnknownBundleAttributes(ReflectionClass $class, ReflectionMethod $method): void
    {
        $this->failOnUnknownAttributes($class->getAttributes(), [$class], $class, $method);
        $this->failOnUnknownAttributes(
            $method->getAttributes(),
            $this->docblockAnnotationGuard->getActionScopes($method),
            $class,
            $method
        );
    }

    /**
     * @param ReflectionAttribute[] $attributes
     * @param ReflectionClass[] $scopes
     */
    private function failOnUnknownAttributes(
        array $attributes,
        array $scopes,
        ReflectionClass $class,
        ReflectionMethod $method
    ): void {
        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if (stripos($name, self::BUNDLE_NAMESPACE) === 0 && !is_a($name, RestAttributeInterface::class, true)) {
                throw new ConfigurationException(sprintf(
                    '%s::%s() uses %s, which is no attribute of the bundle',
                    $class->getName(),
                    $method->getName(),
                    $name
                ));
            }

            $bundleAttribute = $this->findBundleAttributeNamed($this->getShortName($name));
            if ($bundleAttribute !== null && $this->isInNamespaceOf($name, $scopes) && !class_exists($name)) {
                throw new ConfigurationException(sprintf(
                    '%s::%s() uses %s, which is not a class that can be loaded;'
                    . ' if the bundle\'s attribute is meant, the name must resolve to %s',
                    $class->getName(),
                    $method->getName(),
                    $name,
                    $bundleAttribute
                ));
            }
        }
    }

    /**
     * @param ReflectionClass[] $scopes
     */
    private function isInNamespaceOf(string $name, array $scopes): bool
    {
        foreach ($scopes as $scope) {
            $namespace = $scope->getNamespaceName();
            if ($namespace === '' ? strpos($name, '\\') === false : stripos($name, $namespace . '\\') === 0) {
                return true;
            }
        }

        return false;
    }

    private function findBundleAttributeNamed(string $shortName): ?string
    {
        foreach (self::BUNDLE_ATTRIBUTES as $bundleAttribute) {
            if (strcasecmp($this->getShortName($bundleAttribute), $shortName) === 0) {
                return $bundleAttribute;
            }
        }

        return null;
    }

    private function getShortName(string $className): string
    {
        $position = strrpos($className, '\\');

        return $position === false ? $className : substr($className, $position + 1);
    }

    private function createReadException(
        string $source,
        ReflectionClass $class,
        ReflectionMethod $method,
        Throwable $exception
    ): ConfigurationException {
        return new ConfigurationException(
            sprintf(
                'Cannot read the %s of %s::%s(): %s',
                $source,
                $class->getName(),
                $method->getName(),
                $exception->getMessage()
            ),
            0,
            $exception
        );
    }
}
