<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Doctrine\Common\Annotations\Reader;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationGuard;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRouteConfigurator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedFullyQualifiedTraitMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedNamespaceAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedNamespaceDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedTraitMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppAnnotationFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeDocblockReferencesController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsWithNoteTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildImportingForParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildOfImportingParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAndActionDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockAndMethodAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedOptionsTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\GroupUseDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ImportingParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LowercaseAnnotationImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LowercaseFullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingParentInUnrelatedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingParentTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceMentionedInStringController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedTraitActionController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnActionNextToAnnotatedTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnActionNextToNestedTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ParentWithoutImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitFullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TagNamedLikeImportsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedNoteTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnusedAnnotationImportController;
use ReflectionClass;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Loader\AttributeClassLoader;

class RoutingAttributeLoaderTest extends MockeryTestCase
{
    private const ANNOTATION_NAMESPACE = 'Paysera\Bundle\ApiBundle\Annotation';
    private const NOT_READ = ', but docblock annotations of the bundle are not read here, because Symfony passes no'
        . ' annotation reader to the route loader; use the attributes of the bundle instead';

    protected function setUp(): void
    {
        if (!class_exists(AttributeRouteControllerLoader::class)) {
            $this->markTestSkipped('Symfony 6.3 and older load routes through RoutingAnnotationLoader');
        }
    }

    public function testLoadWithAnnotationReaderUsesIt(): void
    {
        if (!property_exists(AttributeClassLoader::class, 'reader')) {
            $this->markTestSkipped('Symfony 7 passes no annotation reader to the route loader');
        }

        $reader = Mockery::mock(Reader::class);
        $reader->shouldReceive('getClassAnnotation')->andReturn(null);
        $reader->shouldReceive('getClassAnnotations')->andReturn([]);
        $reader
            ->shouldReceive('getMethodAnnotations')
            ->andReturn([new stdClass(), new RequiredPermissions(['permissions' => ['ROLE_FROM_READER']])])
        ;

        $this->assertEquals(
            [
                DocblockOptionsOnAttributeRouteController::class . '::show' => (new RestRequestOptions())
                    ->setRequiredPermissions(['ROLE_FROM_READER']),
            ],
            $this->loadOptions($this->createLoader($reader), DocblockOptionsOnAttributeRouteController::class)
        );
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderProvider
     * @param array<string, RestRequestOptions|null> $expectedOptions
     */
    public function testLoadWithoutAnnotationReader(string $controller, array $expectedOptions): void
    {
        $this->assertEquals($expectedOptions, $this->loadOptions($this->createLoader(), $controller));
    }

    public static function loadWithoutAnnotationReaderProvider(): array
    {
        $adminOnly = (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']);

        return [
            'attribute' => [
                AttributeOptionsController::class,
                [AttributeOptionsController::class . '::show' => $adminOnly],
            ],
            'attribute next to a tag that is not an annotation' => [
                AttributeOptionsWithNoteTagController::class,
                [AttributeOptionsWithNoteTagController::class . '::show' => $adminOnly],
            ],
            'attribute through an alias of the bundle namespace' => [
                AliasedNamespaceAttributeController::class,
                [AliasedNamespaceAttributeController::class . '::show' => $adminOnly],
            ],
            'attribute next to docblock references to the attribute classes and a tag of another library' => [
                AttributeDocblockReferencesController::class,
                [AttributeDocblockReferencesController::class . '::show' => $adminOnly],
            ],
            'tags named like imports that are not annotations of the application' => [
                TagNamedLikeImportsController::class,
                [TagNamedLikeImportsController::class . '::show' => $adminOnly],
            ],
            'tag-shaped text in a string and a comment' => [
                NamespaceMentionedInStringController::class,
                [NamespaceMentionedInStringController::class . '::show' => $adminOnly],
            ],
            'tag of another class that cannot be loaded, outside the route\'s docblocks' => [
                MissingParentInUnrelatedDocblockController::class,
                [MissingParentInUnrelatedDocblockController::class . '::show' => $adminOnly],
            ],
            'tag of another class that cannot be loaded, on the action: the class is not loaded' => [
                MissingParentTagController::class,
                [MissingParentTagController::class . '::show' => null],
            ],
            'annotation class of the application, imported: not detected without a reader' => [
                AppAnnotationController::class,
                [AppAnnotationController::class . '::show' => null],
            ],
            'annotation class of the application, fully qualified: not detected without a reader' => [
                AppAnnotationFullyQualifiedController::class,
                [AppAnnotationFullyQualifiedController::class . '::show' => null],
            ],
        ];
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderFailsProvider
     */
    public function testLoadWithoutAnnotationReaderFails(
        string $controller,
        ?string $owner,
        ?string $method,
        string $fileOwner,
        string $finding
    ): void {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage($this->buildRefusalMessage($owner, $method, $fileOwner, $finding));

        $this->createLoader()->load($controller);
    }

    public static function loadWithoutAnnotationReaderFailsProvider(): array
    {
        $imports = 'imports ' . self::ANNOTATION_NAMESPACE . '\\';
        $fullyQualifiedTag = 'has the docblock tag @\\' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions';

        return [
            'annotation on the action' => [
                DocblockOptionsOnAttributeRouteController::class,
                DocblockOptionsOnAttributeRouteController::class,
                'show',
                DocblockOptionsOnAttributeRouteController::class,
                $imports . 'RequiredPermissions',
            ],
            'annotations on the class and the action' => [
                ClassAndActionDocblockController::class,
                ClassAndActionDocblockController::class,
                'show',
                ClassAndActionDocblockController::class,
                $imports . 'RequiredPermissions',
            ],
            'annotation on the class and attribute on the action' => [
                ClassDocblockAndMethodAttributeController::class,
                ClassDocblockAndMethodAttributeController::class,
                'update',
                ClassDocblockAndMethodAttributeController::class,
                $imports . 'RequiredPermissions',
            ],
            'annotation imported with a group use statement' => [
                GroupUseDocblockController::class,
                GroupUseDocblockController::class,
                'show',
                GroupUseDocblockController::class,
                $imports . 'RequiredPermissions',
            ],
            'annotation next to a tag that is not an annotation' => [
                UnimportedNoteTagController::class,
                UnimportedNoteTagController::class,
                'show',
                UnimportedNoteTagController::class,
                $imports . 'RequiredPermissions',
            ],
            'import of an annotation that no docblock uses' => [
                UnusedAnnotationImportController::class,
                UnusedAnnotationImportController::class,
                'show',
                UnusedAnnotationImportController::class,
                $imports . 'Body',
            ],
            'import of an annotation written in lowercase' => [
                LowercaseAnnotationImportController::class,
                LowercaseAnnotationImportController::class,
                'show',
                LowercaseAnnotationImportController::class,
                'imports paysera\bundle\apibundle\annotation\RequiredPermissions',
            ],
            'annotation written through an alias of a parent namespace' => [
                AliasedNamespaceDocblockController::class,
                AliasedNamespaceDocblockController::class,
                'show',
                AliasedNamespaceDocblockController::class,
                'has the docblock tag @Api\Annotation\RequiredPermissions',
            ],
            'fully qualified annotation without a use statement' => [
                FullyQualifiedDocblockController::class,
                FullyQualifiedDocblockController::class,
                'show',
                FullyQualifiedDocblockController::class,
                $fullyQualifiedTag,
            ],
            'fully qualified annotation on the class without a use statement' => [
                ClassDocblockFullyQualifiedController::class,
                ClassDocblockFullyQualifiedController::class,
                'show',
                ClassDocblockFullyQualifiedController::class,
                $fullyQualifiedTag,
            ],
            'fully qualified annotation split across docblock lines' => [
                SplitFullyQualifiedDocblockController::class,
                SplitFullyQualifiedDocblockController::class,
                'show',
                SplitFullyQualifiedDocblockController::class,
                $fullyQualifiedTag,
            ],
            'fully qualified annotation written in lowercase, without arguments' => [
                LowercaseFullyQualifiedDocblockController::class,
                LowercaseFullyQualifiedDocblockController::class,
                'show',
                LowercaseFullyQualifiedDocblockController::class,
                'has the docblock tag @\paysera\bundle\apibundle\annotation\validation',
            ],
            'annotation through an alias that only the routed class imports, for an action of its parent' => [
                ChildImportingForParentController::class,
                ChildImportingForParentController::class,
                'show',
                ParentWithoutImportController::class,
                'has the docblock tag @Rest\RequiredPermissions',
            ],
            'action of a trait that imports the annotation' => [
                TraitDocblockOptionsController::class,
                TraitDocblockOptionsController::class,
                'show',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'action of a trait used by another trait' => [
                NestedTraitActionController::class,
                NestedTraitActionController::class,
                'show',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'own action next to a nested trait that imports the annotation' => [
                OwnActionNextToNestedTraitController::class,
                OwnActionNextToNestedTraitController::class,
                'index',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'own action next to a trait that imports the annotation' => [
                OwnActionNextToAnnotatedTraitController::class,
                null,
                null,
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'trait action renamed with as' => [
                AliasedTraitMethodController::class,
                AliasedTraitMethodController::class,
                'index',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'fully qualified annotation on a trait action renamed with as' => [
                AliasedFullyQualifiedTraitMethodController::class,
                AliasedFullyQualifiedTraitMethodController::class,
                'index',
                FullyQualifiedOptionsTrait::class,
                $fullyQualifiedTag,
            ],
            'attribute-only child of a parent that imports the annotation for an inherited action' => [
                ChildOfImportingParentController::class,
                null,
                null,
                ImportingParentController::class,
                $imports . 'RequiredPermissions',
            ],
        ];
    }

    public function testLoadWithoutAnnotationReaderWhenOpcacheStripsDocComments(): void
    {
        if (!extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('Needs the OPcache extension');
        }

        $attributeOnlyControllers = [
            AttributeOptionsController::class,
            AttributeOptionsWithNoteTagController::class,
            TagNamedLikeImportsController::class,
            MissingParentInUnrelatedDocblockController::class,
        ];
        $annotatedControllers = [
            DocblockOptionsOnAttributeRouteController::class,
            AliasedNamespaceDocblockController::class,
            FullyQualifiedDocblockController::class,
            TraitDocblockOptionsController::class,
        ];
        exec(
            sprintf(
                '%s -d opcache.enable_cli=1 -d opcache.save_comments=0 -d opcache.file_update_protection=0 %s %s',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(__DIR__ . '/Fixtures/load-routes.php'),
                implode(' ', array_map('escapeshellarg', array_merge($attributeOnlyControllers, $annotatedControllers)))
            ),
            $output
        );
        $results = json_decode(implode("\n", $output), true);

        $this->assertIsArray($results, implode("\n", $output));
        $this->assertTrue($results['doc_comments_stripped']);
        foreach ($attributeOnlyControllers as $controller) {
            $this->assertSame([['ROLE_ADMIN']], $results[$controller], $controller);
        }
        foreach ($annotatedControllers as $controller) {
            $this->assertStringContainsString(
                self::NOT_READ,
                (string)json_encode($results[$controller]),
                $controller
            );
        }
    }

    private function buildRefusalMessage(
        ?string $controller,
        ?string $method,
        string $fileOwner,
        string $finding
    ): string {
        return sprintf(
            '%s%s %s%s',
            $controller === null ? '' : sprintf('%s::%s(): ', $controller, $method),
            (new ReflectionClass($fileOwner))->getFileName(),
            $finding,
            self::NOT_READ
        );
    }

    private function createLoader(?Reader $reader = null): RoutingAttributeLoader
    {
        $loader = $reader === null ? new RoutingAttributeLoader() : new RoutingAttributeLoader($reader);
        $loader->setRestRouteConfigurator(new RestRouteConfigurator(
            new RestRequestHelper(new RestRequestOptionsRegistry()),
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new DocblockAnnotationGuard()
        ));

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
