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
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationReader;
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

    /**
     * @dataProvider readerProvider
     */
    public function testLoadFailsWithAnnotationsAndAttributes(?Reader $reader): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            '%s::update() is configured with both docblock annotations and attributes of the bundle',
            NativeRouteMixedOptionsController::class
        ));

        $this->createLoader($reader)->load(NativeRouteMixedOptionsController::class);
    }

    public static function optionsProvider(): array
    {
        $cases = [];
        foreach (self::readerProvider() as $readerCase => [$reader]) {
            $cases['attributes ' . $readerCase] = [NativeRouteAttributeOptionsController::class, $reader];
            $cases['docblock annotations ' . $readerCase] = [NativeRouteDocblockOptionsController::class, $reader];
        }

        return $cases;
    }

    public static function readerProvider(): array
    {
        return [
            'with the reader of Symfony' => [new AnnotationReader()],
            'with framework.annotations disabled' => [null],
        ];
    }

    private function createLoader(?Reader $reader): RoutingAnnotationLoader
    {
        $loader = new RoutingAnnotationLoader($reader);
        $loader->setRestRouteConfigurator(new RestRouteConfigurator(
            new RestRequestHelper(new RestRequestOptionsRegistry()),
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new DocblockAnnotationReader()
        ));

        return $loader;
    }
}
