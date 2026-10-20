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
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltAnnotationAliasAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltAttributeNamespaceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltDocblockAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NonAttributeBundleClassController;
use ReflectionClass;
use ReflectionMethod;
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
    public function testConfigureRouteFailsOnInvalidAttributes(string $controller, string $message): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('PHP 7 has no attributes');
        }

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf($message, $controller));

        $this->configureRoute($controller, null);
    }

    public static function invalidAttributeProvider(): array
    {
        $unknown = '%s::show() uses Paysera\Bundle\ApiBundle\%s, which is no attribute of the bundle';

        return [
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
        ];
    }

    private function configureRoute(string $controller, ?Reader $reader): void
    {
        $configurator = new RestRouteConfigurator(
            new RestRequestHelper(new RestRequestOptionsRegistry()),
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new DocblockAnnotationReader()
        );
        $configurator->configureRoute(
            new Route('/'),
            new ReflectionClass($controller),
            new ReflectionMethod($controller, 'show'),
            $reader
        );
    }
}
