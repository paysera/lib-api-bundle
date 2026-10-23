<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use App\Api\OwnAttributeController;
use App\Controller\MissingUseClassController;
use App\Controller\MissingUseController;
use App\Controller\MissingUseTraitController;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use GlobalMissingUseController;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationGuard;
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
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibraryAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibraryClassAttributeController;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Route;

class RestRouteConfiguratorTest extends MockeryTestCase
{
    private const APPLICATION_FIXTURES = [
        'Api/Body.php',
        'Api/OwnAttributeController.php',
        'Controller/MissingUseClassController.php',
        'Controller/MissingUseController.php',
        'Shared/MissingUseActions.php',
        'Controller/MissingUseTraitController.php',
        'GlobalMissingUseController.php',
    ];

    public function testConfigureRouteWrapsReadErrorsOfTheReader(): void
    {
        try {
            $this->configureRoute(MisspeltDocblockAnnotationController::class, new AnnotationReader());
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

    public function testConfigureRouteWithoutReaderRefusesDocblockAnnotations(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            '%s::show(): %s imports Paysera\Bundle\ApiBundle\Annotation, but docblock annotations of the bundle are'
            . ' not read here, because Symfony passes no annotation reader to the route loader;'
            . ' use the attributes of the bundle instead',
            MisspeltDocblockAnnotationController::class,
            (new ReflectionClass(MisspeltDocblockAnnotationController::class))->getFileName()
        ));

        $this->configureRoute(MisspeltDocblockAnnotationController::class, null);
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
        $this->requireApplicationFixtures();

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
            'name of an attribute without its use statement, in lowercase' => [
                MissingUseController::class,
                sprintf(
                    $missing,
                    '%s',
                    'showLowercaseName',
                    'App\Controller\requiredpermissions',
                    'RequiredPermissions'
                ),
                'showLowercaseName',
            ],
            'qualified name of an attribute without the import of its alias' => [
                MissingUseController::class,
                sprintf(
                    $missing,
                    '%s',
                    'showQualifiedName',
                    'App\Controller\Rest\RequiredPermissions',
                    'RequiredPermissions'
                ),
                'showQualifiedName',
            ],
            'name of an attribute without its use statement, on the class' => [
                MissingUseClassController::class,
                sprintf($missing, '%s', 'show', 'App\Controller\RequiredPermissions', 'RequiredPermissions'),
            ],
            'name of an attribute without its use statement, in a trait of another namespace' => [
                MissingUseTraitController::class,
                sprintf($missing, '%s', 'showFromTrait', 'App\Shared\RequiredPermissions', 'RequiredPermissions'),
                'showFromTrait',
            ],
            'name of an attribute without its use statement, in the global namespace' => [
                GlobalMissingUseController::class,
                sprintf($missing, '%s', 'show', 'RequiredPermissions', 'RequiredPermissions'),
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

            $dataSets['name of an attribute without its use statement: ' . $attribute] = [
                MissingUseController::class,
                sprintf($missing, '%s', 'show' . $attribute, 'App\Controller\\' . $attribute, $attribute),
                'show' . $attribute,
            ];
        }

        return $dataSets;
    }

    /**
     * @dataProvider loadingAttributeProvider
     */
    public function testConfigureRouteLeavesOtherAttributesAlone(string $controller, string $method): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('PHP 7 has no attributes');
        }
        $this->requireApplicationFixtures();

        $this->assertEquals(new Route('/'), $this->configureRoute($controller, null, $method));
    }

    public static function loadingAttributeProvider(): array
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
            'class of the application named like an attribute, in the namespace of the controller' => [
                OwnAttributeController::class,
                'show',
            ],
            'missing class in the namespace of the controller with a name that is none of the attributes' => [
                MissingUseController::class,
                'showMissingClassWithOtherName',
            ],
            'attribute of a package that is not installed, imported' => [
                MissingUseController::class,
                'showUninstalledPackageAttribute',
            ],
            'attribute of a package that is not installed, imported in the global namespace' => [
                GlobalMissingUseController::class,
                'showUninstalledPackageAttribute',
            ],
        ];
    }

    private function requireApplicationFixtures(): void
    {
        foreach (self::APPLICATION_FIXTURES as $fixture) {
            require_once __DIR__ . '/Fixtures/App/' . $fixture;
        }
    }

    private function configureRoute(string $controller, ?Reader $reader, string $method = 'show'): Route
    {
        $configurator = new RestRouteConfigurator(
            new RestRequestHelper(new RestRequestOptionsRegistry()),
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new DocblockAnnotationGuard()
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
