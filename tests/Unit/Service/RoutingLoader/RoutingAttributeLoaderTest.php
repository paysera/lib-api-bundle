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
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationReader;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRouteConfigurator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedFullyQualifiedTraitMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedNamespaceDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedTraitMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppAnnotationFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsWithNoteTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildImportingForParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildOfImportingParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAndActionDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockAndMethodAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\GroupUseDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LowercaseAliasAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingParentInUnrelatedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingParentTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceMentionedInStringController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedTraitActionController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnActionNextToAnnotatedTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnNamespaceAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitAliasAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitFullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TagNamedLikeImportsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedNoteTagController;
use stdClass;
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
            'attribute next to a tag that is not an annotation: the docblock is not read' => [
                AttributeOptionsWithNoteTagController::class,
                [AttributeOptionsWithNoteTagController::class . '::show' => $adminOnly],
            ],
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
            'annotation imported with a group use statement' => [
                GroupUseDocblockController::class,
                [GroupUseDocblockController::class . '::show' => $adminOnly],
            ],
            'annotation written through an alias of a parent namespace' => [
                AliasedNamespaceDocblockController::class,
                [AliasedNamespaceDocblockController::class . '::show' => $adminOnly],
            ],
            'fully qualified annotation without a use statement' => [
                FullyQualifiedDocblockController::class,
                [FullyQualifiedDocblockController::class . '::show' => $adminOnly],
            ],
            'fully qualified annotation on the class without a use statement' => [
                ClassDocblockFullyQualifiedController::class,
                [ClassDocblockFullyQualifiedController::class . '::show' => $adminOnly],
            ],
            'fully qualified annotation on a trait action renamed with as' => [
                AliasedFullyQualifiedTraitMethodController::class,
                [AliasedFullyQualifiedTraitMethodController::class . '::index' => $adminOnly],
            ],
            'fully qualified annotation split across docblock lines' => [
                SplitFullyQualifiedDocblockController::class,
                [SplitFullyQualifiedDocblockController::class . '::show' => $adminOnly],
            ],
            'annotation class of the application, imported' => [
                AppAnnotationController::class,
                [
                    AppAnnotationController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_AUDITOR']),
                ],
            ],
            'annotation class of the application, fully qualified without a use statement' => [
                AppAnnotationFullyQualifiedController::class,
                [
                    AppAnnotationFullyQualifiedController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_AUDITOR']),
                ],
            ],
            'annotation class of the application, written through an alias of its namespace' => [
                NamespaceAliasAppAnnotationController::class,
                [
                    NamespaceAliasAppAnnotationController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_AUDITOR']),
                ],
            ],
            'annotation class of the application, through a namespace alias split across docblock lines' => [
                SplitAliasAppAnnotationController::class,
                [
                    SplitAliasAppAnnotationController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_AUDITOR']),
                ],
            ],
            'annotation class of the application, imported with a lowercase alias' => [
                LowercaseAliasAppAnnotationController::class,
                [
                    LowercaseAliasAppAnnotationController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_AUDITOR']),
                ],
            ],
            'annotation class of the application, in the namespace of the controller' => [
                OwnNamespaceAppAnnotationController::class,
                [
                    OwnNamespaceAppAnnotationController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_AUDITOR']),
                ],
            ],
            'unloadable class tagged outside the route\'s docblocks: the route still loads' => [
                MissingParentInUnrelatedDocblockController::class,
                [MissingParentInUnrelatedDocblockController::class . '::show' => $adminOnly],
            ],
            'attribute-only child of an annotated parent: only the inherited action\'s docblock is read' => [
                ChildOfImportingParentController::class,
                [
                    ChildOfImportingParentController::class . '::show' => $adminOnly,
                    ChildOfImportingParentController::class . '::index' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_USER']),
                ],
            ],
            'own action next to an annotated trait: only the trait action\'s docblock is read' => [
                OwnActionNextToAnnotatedTraitController::class,
                [
                    OwnActionNextToAnnotatedTraitController::class . '::show' => $adminOnly,
                    OwnActionNextToAnnotatedTraitController::class . '::index' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_USER']),
                ],
            ],
            'tags named like imports that are not annotations of the application: the docblock is not read' => [
                TagNamedLikeImportsController::class,
                [TagNamedLikeImportsController::class . '::show' => $adminOnly],
            ],
            'tag-shaped text in a string and a comment: the docblock is not read' => [
                NamespaceMentionedInStringController::class,
                [NamespaceMentionedInStringController::class . '::show' => $adminOnly],
            ],
        ];
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderFailsProvider
     */
    public function testLoadWithoutAnnotationReaderFails(string $controller, string $expectedMessage): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->createLoader()->load($controller);
    }

    public static function loadWithoutAnnotationReaderFailsProvider(): array
    {
        return [
            'a tag that is not an annotation next to the annotation' => [
                UnimportedNoteTagController::class,
                sprintf(
                    'Cannot read the docblock annotations of %s::show(): [Semantical Error] The annotation "@note"',
                    UnimportedNoteTagController::class
                ),
            ],
            'annotation on the class and attribute on the action' => [
                ClassDocblockAndMethodAttributeController::class,
                sprintf(
                    '%s::update() is configured with both docblock annotations and attributes of the bundle',
                    ClassDocblockAndMethodAttributeController::class
                ),
            ],
            'action of a trait used by another trait, whose imports Doctrine\'s reader does not see' => [
                NestedTraitActionController::class,
                sprintf(
                    'Cannot read the docblock annotations of %s::show(): [Semantical Error] The annotation'
                    . ' "@RequiredPermissions"',
                    NestedTraitActionController::class
                ),
            ],
            'trait action renamed with as, whose imports Doctrine\'s reader does not see' => [
                AliasedTraitMethodController::class,
                sprintf(
                    'Cannot read the docblock annotations of %s::index(): [Semantical Error] The annotation'
                    . ' "@RequiredPermissions"',
                    AliasedTraitMethodController::class
                ),
            ],
            'alias imported by the routed class only, for an action of its parent, refused by Doctrine' => [
                ChildImportingForParentController::class,
                sprintf(
                    'Cannot read the docblock annotations of %s::show(): [Semantical Error] The annotation'
                    . ' "@Rest\RequiredPermissions"',
                    ChildImportingForParentController::class
                ),
            ],
            'unloadable class tagged on the action' => [
                MissingParentTagController::class,
                sprintf(
                    'Cannot read the docblock annotations of %s::show(): Class "%s" not found',
                    MissingParentTagController::class,
                    'Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingParent'
                ),
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
        $importingControllers = [
            DocblockOptionsOnAttributeRouteController::class,
            AliasedNamespaceDocblockController::class,
            ClassAndActionDocblockController::class,
        ];
        exec(
            sprintf(
                '%s -d opcache.enable_cli=1 -d opcache.save_comments=0 -d opcache.file_update_protection=0 %s %s',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(__DIR__ . '/Fixtures/load-routes.php'),
                implode(' ', array_map('escapeshellarg', array_merge($attributeOnlyControllers, $importingControllers)))
            ),
            $output
        );
        $results = json_decode(implode("\n", $output), true);

        $this->assertIsArray($results, implode("\n", $output));
        $this->assertTrue($results['doc_comments_stripped']);
        foreach ($attributeOnlyControllers as $controller) {
            $this->assertSame([['ROLE_ADMIN']], $results[$controller], $controller);
        }
        foreach ($importingControllers as $controller) {
            $this->assertStringContainsString(
                'save_comments=1',
                (string)json_encode($results[$controller]),
                $controller
            );
        }
    }

    private function createLoader(?Reader $reader = null): RoutingAttributeLoader
    {
        $loader = $reader === null ? new RoutingAttributeLoader() : new RoutingAttributeLoader($reader);
        $loader->setRestRouteConfigurator(new RestRouteConfigurator(
            new RestRequestHelper(new RestRequestOptionsRegistry()),
            new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
            new DocblockAnnotationReader()
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
