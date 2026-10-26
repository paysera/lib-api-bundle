<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationGuard;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRouteConfigurator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAnnotationLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NativeRouteAttributeOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NativeRouteDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NativeRouteMixedOptionsController;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Annotation\Route;

class RoutingAnnotationLoaderTest extends MockeryTestCase
{
    private const NOT_READ = ', but docblock annotations of the bundle are not read here, because Symfony passes no'
        . ' annotation reader to the route loader; use the attributes of the bundle instead';

    protected function setUp(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('PHP 7 has no attributes');
        }

        if (class_exists(AttributeRouteControllerLoader::class)) {
            $this->markTestSkipped('Symfony 6.4 and later load routes through RoutingAttributeLoader');
        }

        if ((new ReflectionClass(Route::class))->getAttributes() === []) {
            $this->markTestSkipped('Symfony 5.1 and older read no #[Route] attributes');
        }
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testLoadAppliesOptions(string $controller, ?Reader $reader): void
    {
        $routes = $this->createLoader($reader)->load($controller)->all();

        $this->assertCount(1, $routes);
        $this->assertEquals(
            (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']),
            unserialize(reset($routes)->getDefault(RestRequestHelper::SERIALIZED_REST_OPTIONS_KEY))
        );
    }

    public static function optionsProvider(): array
    {
        return [
            'attributes with the reader of Symfony' => [
                NativeRouteAttributeOptionsController::class,
                new AnnotationReader(),
            ],
            'attributes with framework.annotations disabled' => [NativeRouteAttributeOptionsController::class, null],
            'docblock annotations with the reader of Symfony' => [
                NativeRouteDocblockOptionsController::class,
                new AnnotationReader(),
            ],
        ];
    }

    /**
     * @dataProvider failureProvider
     */
    public function testLoadFails(string $controller, ?Reader $reader, string $expectedMessage): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->createLoader($reader)->load($controller);
    }

    public static function failureProvider(): array
    {
        return [
            'docblock annotations with framework.annotations disabled' => [
                NativeRouteDocblockOptionsController::class,
                null,
                sprintf(
                    '%s::show(): %s imports Paysera\Bundle\ApiBundle\Annotation%s',
                    NativeRouteDocblockOptionsController::class,
                    (new ReflectionClass(NativeRouteDocblockOptionsController::class))->getFileName(),
                    self::NOT_READ
                ),
            ],
            'docblock annotations and attributes with the reader of Symfony' => [
                NativeRouteMixedOptionsController::class,
                new AnnotationReader(),
                sprintf(
                    '%s::update() is configured with both docblock annotations and attributes of the bundle',
                    NativeRouteMixedOptionsController::class
                ),
            ],
            'docblock annotations and attributes with framework.annotations disabled' => [
                NativeRouteMixedOptionsController::class,
                null,
                sprintf(
                    '%s::update(): %s imports Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions%s',
                    NativeRouteMixedOptionsController::class,
                    (new ReflectionClass(NativeRouteMixedOptionsController::class))->getFileName(),
                    self::NOT_READ
                ),
            ],
        ];
    }

    private function createLoader(?Reader $reader): RoutingAnnotationLoader
    {
        $loader = new RoutingAnnotationLoader($reader);
        $loader->setRestRouteConfigurator(new RestRouteConfigurator(
            new RestRequestHelper(new RestRequestOptionsRegistry()),
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new DocblockAnnotationGuard()
        ));

        return $loader;
    }
}
