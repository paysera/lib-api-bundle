<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\Reader;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAndActionDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockAndMethodAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MethodDocblockAndAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NoBundleAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedAnnotationDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedNoteTagController;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Loader\AttributeClassLoader;

class RoutingAttributeLoaderTest extends MockeryTestCase
{
    protected function setUp(): void
    {
        if (!class_exists(AttributeRouteControllerLoader::class)) {
            $this->markTestSkipped('Symfony 6.3 and older load routes through RoutingAnnotationLoader');
        }
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderDataProvider
     * @param array<string, RestRequestOptions|null> $expectedOptions
     */
    public function testLoadWithoutAnnotationReader(string $controller, array $expectedOptions): void
    {
        $loader = $this->createLoader();
        $loader->setDocblockAnnotationsEnabled(true);

        $this->assertEquals($expectedOptions, $this->loadOptions($loader, $controller));
    }

    public static function loadWithoutAnnotationReaderDataProvider(): array
    {
        $adminOnly = (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']);

        return [
            'annotation on the action' => [
                DocblockOptionsOnAttributeRouteController::class,
                [DocblockOptionsOnAttributeRouteController::class . '::show' => $adminOnly],
            ],
            'annotations on the class and the action' => [
                ClassAndActionDocblockController::class,
                [
                    ClassAndActionDocblockController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_USER', 'ROLE_ADMIN'])
                        ->disableBodyValidation(),
                ],
            ],
            'action of a trait that imports the annotation' => [
                TraitDocblockOptionsController::class,
                [TraitDocblockOptionsController::class . '::show' => $adminOnly],
            ],
            'attribute' => [
                AttributeOptionsController::class,
                [AttributeOptionsController::class . '::show' => $adminOnly],
            ],
            'no annotation of the bundle' => [
                NoBundleAnnotationController::class,
                [NoBundleAnnotationController::class . '::show' => null],
            ],
        ];
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderFailureDataProvider
     */
    public function testLoadWithoutAnnotationReaderFails(string $controller, string $exception, string $message): void
    {
        $loader = $this->createLoader();
        $loader->setDocblockAnnotationsEnabled(true);

        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $loader->load($controller);
    }

    public static function loadWithoutAnnotationReaderFailureDataProvider(): array
    {
        return [
            'annotation of the bundle that is not imported, refused as by Doctrine\'s reader' => [
                UnimportedAnnotationDocblockController::class,
                AnnotationException::class,
                'The annotation "@RequiredPermissions" in method ' . UnimportedAnnotationDocblockController::class
                . '::show() was never imported',
            ],
            'tag that is not imported and not an annotation, refused as by Doctrine\'s reader' => [
                UnimportedNoteTagController::class,
                AnnotationException::class,
                'The annotation "@note" in method ' . UnimportedNoteTagController::class
                . '::show() was never imported',
            ],
            'annotation on the class and attribute on the action' => [
                ClassDocblockAndMethodAttributeController::class,
                ConfigurationException::class,
                'The route of method ' . ClassDocblockAndMethodAttributeController::class . '::update() is configured'
                . ' with both docblock annotations and attributes of the bundle',
            ],
            'annotation and attribute on the action' => [
                MethodDocblockAndAttributeController::class,
                ConfigurationException::class,
                'The route of method ' . MethodDocblockAndAttributeController::class . '::show() is configured'
                . ' with both docblock annotations and attributes of the bundle',
            ],
        ];
    }

    /**
     * @dataProvider loadWithAnnotationReaderDataProvider
     */
    public function testLoadWithAnnotationReader(bool $docblockAnnotationsEnabled): void
    {
        if (!property_exists(AttributeClassLoader::class, 'reader')) {
            $this->markTestSkipped('Symfony 7 passes no annotation reader to the route loader');
        }

        $reader = Mockery::mock(Reader::class);
        $reader->shouldReceive('getClassAnnotation')->andReturn(null);
        $reader->shouldReceive('getClassAnnotations')->andReturn([]);
        $reader->shouldReceive('getMethodAnnotations')->andReturn([
            new RequiredPermissions(['permissions' => ['ROLE_ADMIN']]),
        ]);
        $loader = $this->createLoader($reader);
        $loader->setDocblockAnnotationsEnabled($docblockAnnotationsEnabled);

        $this->assertEquals(
            [
                NoBundleAnnotationController::class . '::show' => (new RestRequestOptions())
                    ->setRequiredPermissions(['ROLE_ADMIN']),
            ],
            $this->loadOptions($loader, NoBundleAnnotationController::class)
        );
    }

    public static function loadWithAnnotationReaderDataProvider(): array
    {
        return [
            'docblock annotations on' => [true],
            'docblock annotations off, which applies only where no reader is injected' => [false],
        ];
    }

    /**
     * @dataProvider loadWithAnnotationReaderDataProvider
     */
    public function testLoadWithAnnotationReaderFailsOnMixedRoute(bool $docblockAnnotationsEnabled): void
    {
        if (!property_exists(AttributeClassLoader::class, 'reader')) {
            $this->markTestSkipped('Symfony 7 passes no annotation reader to the route loader');
        }

        $reader = Mockery::mock(Reader::class);
        $reader->shouldReceive('getClassAnnotation')->andReturn(null);
        $reader->shouldReceive('getClassAnnotations')->andReturn([]);
        $reader->shouldReceive('getMethodAnnotations')->andReturn([
            new RequiredPermissions(['permissions' => ['ROLE_USER']]),
        ]);
        $loader = $this->createLoader($reader);
        $loader->setDocblockAnnotationsEnabled($docblockAnnotationsEnabled);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'The route of method ' . AttributeOptionsController::class . '::show() is configured'
            . ' with both docblock annotations and attributes of the bundle'
        );

        $loader->load(AttributeOptionsController::class);
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderByDefaultDataProvider
     * @param array<string, RestRequestOptions|null> $expectedOptions
     */
    public function testLoadWithoutAnnotationReaderReadsOnlyAttributesByDefault(
        string $controller,
        array $expectedOptions
    ): void {
        $this->assertEquals($expectedOptions, $this->loadOptions($this->createLoader(), $controller));
    }

    public static function loadWithoutAnnotationReaderByDefaultDataProvider(): array
    {
        return [
            'docblock annotation' => [
                DocblockOptionsOnAttributeRouteController::class,
                [DocblockOptionsOnAttributeRouteController::class . '::show' => null],
            ],
            'attribute' => [
                AttributeOptionsController::class,
                [
                    AttributeOptionsController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_ADMIN']),
                ],
            ],
            'docblock annotation and attribute on the action: only the attribute is read' => [
                MethodDocblockAndAttributeController::class,
                [
                    MethodDocblockAndAttributeController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_ADMIN']),
                ],
            ],
        ];
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderUnderOpcacheDataProvider
     * @param string[] $ini
     * @param string[] $arguments
     */
    public function testLoadWithoutAnnotationReaderUnderOpcache(
        array $ini,
        string $expectedOutput,
        array $arguments
    ): void {
        $lines = $this->runFixtureScript($ini, 'load-routes.php', $arguments);
        $output = (string)end($lines);
        if ($output === 'no opcache') {
            $this->markTestSkipped('This PHP has no OPcache to strip docblocks');
        }

        $this->assertSame($expectedOutput, $output, implode("\n", $lines));
    }

    public static function loadWithoutAnnotationReaderUnderOpcacheDataProvider(): array
    {
        $docblocksStripped = [
            '-d',
            'opcache.enable_cli=1',
            '-d',
            'opcache.save_comments=0',
            '-d',
            'opcache.file_update_protection=0',
        ];

        return [
            'attributes only, docblocks stripped, default configuration' => [
                $docblocksStripped,
                '["ROLE_ADMIN"]',
                [AttributeOptionsController::class],
            ],
            'docblock annotations on, docblocks stripped: Doctrine\'s reader refuses to run' => [
                $docblocksStripped,
                AnnotationException::class,
                [DocblockOptionsOnAttributeRouteController::class, 'docblock-annotations'],
            ],
            'docblock annotations on, docblocks kept' => [
                [
                    '-d',
                    'opcache.enable_cli=1',
                    '-d',
                    'opcache.save_comments=1',
                    '-d',
                    'opcache.file_update_protection=0',
                ],
                '["ROLE_ADMIN"]',
                [DocblockOptionsOnAttributeRouteController::class, 'docblock-annotations'],
            ],
            'docblock annotations on, save_comments off with OPcache off for the command line: refused as well' => [
                ['-d', 'opcache.enable_cli=0', '-d', 'opcache.save_comments=0'],
                AnnotationException::class,
                [DocblockOptionsOnAttributeRouteController::class, 'docblock-annotations'],
            ],
        ];
    }

    /**
     * @param string[] $ini
     * @param string[] $arguments
     * @return string[] the lines the script printed, standard error included
     */
    private function runFixtureScript(array $ini, string $script, array $arguments): array
    {
        $autoload = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2) . '/autoload.php';
        $command = array_merge([PHP_BINARY], $ini, [__DIR__ . '/Fixtures/' . $script, $autoload], $arguments);
        exec(implode(' ', array_map('escapeshellarg', $command)) . ' 2>&1', $lines);

        return $lines;
    }

    private function createLoader(?Reader $reader = null): RoutingAttributeLoader
    {
        $loader = $reader === null ? new RoutingAttributeLoader() : new RoutingAttributeLoader($reader);
        $loader->setRequestHelper(new RestRequestHelper(new RestRequestOptionsRegistry()));
        $loader->setRestRequestAnnotationOptionsBuilder(
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class))
        );
        $loader->setRestRequestAttributeOptionsBuilder(
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class))
        );

        return $loader;
    }

    /**
     * @return array<string, RestRequestOptions|null>
     */
    private function loadOptions(RoutingAttributeLoader $loader, string $controller): array
    {
        $options = [];
        foreach ($loader->load($controller)->all() as $route) {
            $serializedOptions = $route->getDefault(RestRequestHelper::SERIALIZED_REST_OPTIONS_KEY);
            $options[$route->getDefault('_controller')] = $serializedOptions === null
                ? null
                : unserialize($serializedOptions);
        }

        return $options;
    }
}
