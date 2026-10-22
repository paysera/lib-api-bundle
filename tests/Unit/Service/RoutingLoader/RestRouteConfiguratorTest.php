<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationReader;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRouteConfigurator;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AnnotationClassAsAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\InvalidAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LowercaseMisspeltAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingAttributeClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingClassAttributeClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltAnnotationAliasAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltAttributeNamespaceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltDocblockAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NonAttributeBundleClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibraryAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibraryClassAttributeController;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Route;

class RestRouteConfiguratorTest extends MockeryTestCase
{
    /**
     * @dataProvider readerProvider
     */
    public function testConfigureRouteWrapsReadErrors(?Reader $reader): void
    {
        try {
            $this->configureRoute(MisspeltDocblockAnnotationController::class, $reader);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringStartsWith(
                sprintf(
                    'Cannot read the docblock annotations of %s::show(): [Semantical Error]',
                    MisspeltDocblockAnnotationController::class
                ),
                $exception->getMessage()
            );
            $this->assertInstanceOf(AnnotationException::class, $exception->getPrevious());
        }
    }

    public static function readerProvider(): array
    {
        return [
            'with the reader of Symfony' => [new AnnotationReader()],
            'without a reader' => [null],
        ];
    }

    /**
     * @dataProvider invalidAttributeProvider
     */
    public function testConfigureRouteFailsOnInvalidAttributes(
        string $controller,
        string $message,
        string $method = 'show'
    ): void {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('PHP 7 has no attributes');
        }

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf($message, $controller));

        $this->configureRoute($controller, null, $method);
    }

    public static function invalidAttributeProvider(): array
    {
        $unknown = '%s::show() uses Paysera\Bundle\ApiBundle\%s, which is no attribute of the bundle';
        $missing = '%s::%s() uses %s, which is not a class that can be loaded;'
            . ' if the bundle\'s attribute is meant, the name must resolve to Paysera\Bundle\ApiBundle\Attribute\%s';

        $dataSets = [
            'constructor error' => [InvalidAttributeController::class, 'Cannot read the attributes of %s::show(): '],
            'misspelt name' => [
                MisspeltAttributeController::class,
                sprintf($unknown, '%s', 'Attribute\RequiredPermission'),
            ],
            'misspelt namespace' => [
                MisspeltAttributeNamespaceController::class,
                sprintf($unknown, '%s', 'Attributes\RequiredPermissions'),
            ],
            'misspelt namespace written in lowercase' => [
                LowercaseMisspeltAttributeController::class,
                '%s::show() uses paysera\bundle\apibundle\attributes\RequiredPermissions,'
                . ' which is no attribute of the bundle',
            ],
            'misspelt name in the annotation namespace' => [
                MisspeltAnnotationAliasAttributeController::class,
                sprintf($unknown, '%s', 'Annotation\RequiredPermission'),
            ],
            'class of the bundle that is no attribute' => [
                NonAttributeBundleClassController::class,
                sprintf($unknown, '%s', 'Entity\RestRequestOptions'),
            ],
            'annotation class used as an attribute' => [
                AnnotationClassAsAttributeController::class,
                'Cannot read the attributes of %s::show(): Attempting to use non-attribute class',
            ],
            'missing class named like an attribute in lowercase' => [
                MissingAttributeClassController::class,
                sprintf(
                    $missing,
                    '%s',
                    'showLowercaseName',
                    'App\Controller\requiredpermissions',
                    'RequiredPermissions'
                ),
                'showLowercaseName',
            ],
            'missing class in the global namespace named like an attribute' => [
                MissingAttributeClassController::class,
                sprintf($missing, '%s', 'showGlobalName', 'RequiredPermissions', 'RequiredPermissions'),
                'showGlobalName',
            ],
            'missing class named like an attribute, on the class' => [
                MissingClassAttributeClassController::class,
                sprintf($missing, '%s', 'show', 'App\Controller\RequiredPermissions', 'RequiredPermissions'),
            ],
        ];

        $attributePattern = __DIR__ . '/../../../../src/Attribute/*.php';
        $attributeFiles = glob($attributePattern);
        if ($attributeFiles === false || $attributeFiles === []) {
            throw new RuntimeException('No attribute class found by the pattern ' . $attributePattern);
        }

        foreach ($attributeFiles as $file) {
            $attribute = basename($file, '.php');
            if (interface_exists('Paysera\Bundle\ApiBundle\Attribute\\' . $attribute)) {
                continue;
            }

            $dataSets['missing class named ' . $attribute] = [
                MissingAttributeClassController::class,
                sprintf($missing, '%s', 'show' . $attribute, 'App\Controller\\' . $attribute, $attribute),
                'show' . $attribute,
            ];
        }

        return $dataSets;
    }

    /**
     * @dataProvider otherLibraryAttributeProvider
     */
    public function testConfigureRouteIgnoresAttributesOfOtherLibraries(string $controller, string $method): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('PHP 7 has no attributes');
        }

        $this->assertEquals(new Route('/'), $this->configureRoute($controller, null, $method));
    }

    public static function otherLibraryAttributeProvider(): array
    {
        return [
            'class of another library named like an attribute' => [
                OtherLibraryAttributeController::class,
                'showClassNamedLikeAttribute',
            ],
            'class of another library named like an attribute, on the class' => [
                OtherLibraryClassAttributeController::class,
                'show',
            ],
            'missing class with a name that is none of the attributes' => [
                OtherLibraryAttributeController::class,
                'showMissingClassWithOtherName',
            ],
        ];
    }

    private function configureRoute(string $controller, ?Reader $reader, string $method = 'show'): Route
    {
        $configurator = new RestRouteConfigurator(
            new RestRequestHelper(new RestRequestOptionsRegistry()),
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new DocblockAnnotationReader()
        );
        $route = new Route('/');
        $configurator->configureRoute(
            $route,
            new ReflectionClass($controller),
            new ReflectionMethod($controller, $method),
            $reader
        );

        return $route;
    }
}
