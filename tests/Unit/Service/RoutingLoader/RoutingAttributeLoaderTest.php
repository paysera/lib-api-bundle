<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\Reader;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\Validation as ValidationAnnotation;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\NamespaceRelativeDocblockController;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasAndBareDuplicateController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedTraitMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AnnotationClassConstantController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AnnotationClassNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\BareColonInTagNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\BracedAliasAfterAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\BracedAliasProseController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\BundleAnnotationNextToUnrelatedTagsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\BundleAnnotationOnUnroutedMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAliasDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAliasedOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAliasImportedByTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAndActionDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAnnotationWithImportedConstantController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAnnotationOnInheritedRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassConstantNamedLikeAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockWithTraitImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassImportingForNestedTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassImportingForTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassNotImportingForTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonAfterAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonAfterBackslashOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonAfterClassAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonBeforeBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonInImportedOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonInOtherTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonInOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ColonInTagNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DashAfterAliasProseController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DashedBundleTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DeprecatedParenthesisController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DotInsteadOfBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DoubleBackslashDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DoubleColonAfterAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DoubledBackslashAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FirstNameSharedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ForeignAnnotationClassConstantController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ForeignAnnotationTagAndClassNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedDoubledBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedMiscasedTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedMissingClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\IgnoredImportedAliasSpacedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\IgnoredMixinBeforeAnnotationClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\IgnoredNonAnnotationClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\IgnoredUnimportedAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ImportedConstantsDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\InheritedDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LeadingBackslashMiscasedImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LeadingBackslashSpacedAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LeadingBackslashSpacedParentAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LeadingBackslashSpaceNotBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MethodAnnotationOnClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedAliasForTagAndReferenceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedAliasImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedAliasMisspelledController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedAliasTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedFirstNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedImportDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedImportNextToRejectedTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MiscasedOwnAliasImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingBundleSubNamespaceImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspelledBundleAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspelledBundleImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspelledFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspelledImportDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasForTagAndReferenceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasNamedLikeAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasPathReferencesDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasReferencesDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasWithoutAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedAfterColonController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedAfterCommaController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedAfterParenthesisController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedBlameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedDoubledBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedInBracesController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedTraitDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedUnimportedAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedUnimportedAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NewlineUnimportedAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NoBundleAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NonAnnotationImportDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibrary\SameNamespaceAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibraryDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnActionUsingAnnotatedTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnActionWithTraitImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnAnnotationThenClassNameLineController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnAnnotationDoubledBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnAnnotationFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnAnnotationImportedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnAnnotationInControllerNamespaceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnAnnotationRelativeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnAnnotationThroughNamespaceAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ParentNamespaceAliasDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ProseWithAliasTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\QuoteBeforeAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\QuoteBeforeOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\QuotedTagNameAndReferenceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\RelativeForeignAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SameNamespaceReferencesDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SeeParenthesisAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SeeTagBeforeAnnotationClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SelfConstantDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SlashInsteadOfBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpaceAfterAtController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpaceAfterParentAliasPathController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpaceAfterBackslashAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpaceBeforeBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpaceInsteadOfBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpacedBackslashUnimportedAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpacedDoubleBackslashOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpacedForeignAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpacedOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SpacedUnimportedAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitBackslashAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitBackslashOwnAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitBackslashUnimportedAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitMisspelledAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitNameDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\Validation;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SyntaxErrorNextToUnimportedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TagStartingWithAnnotationNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TagsOnlyDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TodoParenthesisAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitAliasCollidingWithClassAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitsInTwoNamespacesController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TwoTraitsInOneFileController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedAnnotationDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedBareColonController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedColonBeforeBackslashController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedForeignNamespaceAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedNamespaceAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedNamespaceAliasOnClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedSpacedAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedNamespaceSpaceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedSplitAliasController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnqualifiedGlobalAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnquotedIgnoreAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnrelatedDocblockTagsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnusedBundleImportController;
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

        require_once __DIR__ . '/Fixtures/NamespaceRelativeDocblockController.php';
        require_once __DIR__ . '/Fixtures/TraitsInTwoNamespaces.php';
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderDataProvider
     * @param array<string, RestRequestOptions|null> $expectedOptions
     */
    public function testLoadWithoutAnnotationReader(string $controller, array $expectedOptions): void
    {
        $this->assertEquals($expectedOptions, $this->loadOptions($this->createLoader(), $controller));
    }

    public static function loadWithoutAnnotationReaderDataProvider(): array
    {
        $adminOnly = (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']);
        $auditorOnly = (new RestRequestOptions())->setRequiredPermissions(['ROLE_AUDITOR']);

        return [
            'short name' => [
                DocblockOptionsOnAttributeRouteController::class,
                [DocblockOptionsOnAttributeRouteController::class . '::show' => $adminOnly],
            ],
            'namespace alias, on the class' => [
                NamespaceAliasDocblockController::class,
                [NamespaceAliasDocblockController::class . '::show' => $adminOnly],
            ],
            'fully qualified name' => [
                FullyQualifiedDocblockController::class,
                [FullyQualifiedDocblockController::class . '::show' => $adminOnly],
            ],
            'fully qualified name with two leading backslashes' => [
                DoubleBackslashDocblockController::class,
                [DoubleBackslashDocblockController::class . '::show' => $adminOnly],
            ],
            'namespace alias with the name continued on the next line' => [
                SplitNameDocblockController::class,
                [SplitNameDocblockController::class . '::show' => $adminOnly],
            ],
            'class alias' => [
                ClassAliasDocblockController::class,
                [ClassAliasDocblockController::class . '::show' => $adminOnly],
            ],
            'alias of a parent namespace, imported with a leading backslash' => [
                ParentNamespaceAliasDocblockController::class,
                [ParentNamespaceAliasDocblockController::class . '::show' => $adminOnly],
            ],
            'annotation on both the class and the action' => [
                ClassAndActionDocblockController::class,
                [
                    ClassAndActionDocblockController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_USER', 'ROLE_ADMIN'])
                        ->disableBodyValidation(),
                ],
            ],
            'method inherited from a parent class that imports the annotation' => [
                InheritedDocblockOptionsController::class,
                [InheritedDocblockOptionsController::class . '::show' => $adminOnly],
            ],
            'annotation on a class whose route is inherited' => [
                ClassAnnotationOnInheritedRouteController::class,
                [ClassAnnotationOnInheritedRouteController::class . '::show' => $adminOnly],
            ],
            'method of a trait that imports the annotation' => [
                TraitDocblockOptionsController::class,
                [TraitDocblockOptionsController::class . '::show' => $adminOnly],
            ],
            'method of a trait whose annotation the class imports' => [
                ClassImportingForTraitController::class,
                [ClassImportingForTraitController::class . '::index' => $adminOnly],
            ],
            'method of a trait used by a trait that imports the annotation' => [
                NestedTraitDocblockOptionsController::class,
                [NestedTraitDocblockOptionsController::class . '::show' => $adminOnly],
            ],
            'method of a trait used by a trait whose annotation the class imports' => [
                ClassImportingForNestedTraitController::class,
                [ClassImportingForNestedTraitController::class . '::index' => $adminOnly],
            ],
            'method of a trait under an alias' => [
                AliasedTraitMethodController::class,
                [
                    AliasedTraitMethodController::class . '::show' => $adminOnly,
                    AliasedTraitMethodController::class . '::index' => $adminOnly,
                ],
            ],
            'method of a trait whose import shares its alias with an import of the class' => [
                TraitAliasCollidingWithClassAliasController::class,
                [TraitAliasCollidingWithClassAliasController::class . '::show' => $adminOnly],
            ],
            'method of a trait whose file declares a trait with a colliding import in another namespace' => [
                TraitsInTwoNamespacesController::class,
                [TraitsInTwoNamespacesController::class . '::show' => $adminOnly],
            ],
            'method of the second of two traits in one file' => [
                TwoTraitsInOneFileController::class,
                [TwoTraitsInOneFileController::class . '::show' => $adminOnly],
            ],
            'annotation named through the namespace of the controller' => [
                NamespaceRelativeDocblockController::class,
                [NamespaceRelativeDocblockController::class . '::show' => $adminOnly],
            ],
            'constants of an imported class in the arguments' => [
                ImportedConstantsDocblockController::class,
                [
                    ImportedConstantsDocblockController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_ADMIN'])
                        ->setResponseNormalizationType(RestRequestOptions::class),
                ],
            ],
            'class names relative to the namespace of the controller in the arguments' => [
                SameNamespaceReferencesDocblockController::class,
                [
                    SameNamespaceReferencesDocblockController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_ADMIN'])
                        ->setResponseNormalizationType(NoBundleAnnotationController::class),
                ],
            ],
            'class name through a namespace alias in the arguments' => [
                NamespaceAliasReferencesDocblockController::class,
                [NamespaceAliasReferencesDocblockController::class . '::show' => $adminOnly],
            ],
            'class name through a namespace alias and several namespace segments in the arguments' => [
                NamespaceAliasPathReferencesDocblockController::class,
                [NamespaceAliasPathReferencesDocblockController::class . '::show' => $adminOnly],
            ],
            'alias also used for a tag of another library: tag ignored, class name kept as written' => [
                NamespaceAliasForTagAndReferenceController::class,
                [
                    NamespaceAliasForTagAndReferenceController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_ADMIN'])
                        ->setResponseNormalizationType('Lib\\SameNamespaceAnnotationController'),
                ],
            ],
            'alias also used for a miscased tag of another library: tag ignored, class name kept as written' => [
                MiscasedAliasForTagAndReferenceController::class,
                [
                    MiscasedAliasForTagAndReferenceController::class . '::show' => (new RestRequestOptions())
                        ->setRequiredPermissions(['ROLE_ADMIN'])
                        ->setResponseNormalizationType('Lib\\SameNamespaceAnnotationController'),
                ],
            ],
            'constant of an imported class named like an annotation of the bundle' => [
                ClassConstantNamedLikeAnnotationController::class,
                [ClassConstantNamedLikeAnnotationController::class . '::show' => $adminOnly],
            ],
            'class name through a namespace alias named like an annotation of the bundle' => [
                NamespaceAliasNamedLikeAnnotationController::class,
                [NamespaceAliasNamedLikeAnnotationController::class . '::show' => $adminOnly],
            ],
            'namespace alias named like an annotation of the bundle, next to a longer tag starting with that name' => [
                TagStartingWithAnnotationNameController::class,
                [TagStartingWithAnnotationNameController::class . '::show' => $adminOnly],
            ],
            'constant of the controller through self' => [
                SelfConstantDocblockController::class,
                [SelfConstantDocblockController::class . '::show' => $adminOnly],
            ],
            'own annotation of the application, imported' => [
                OwnAnnotationImportedController::class,
                [OwnAnnotationImportedController::class . '::show' => $auditorOnly],
            ],
            'own annotation of the application, in the namespace of the controller' => [
                OwnAnnotationInControllerNamespaceController::class,
                [OwnAnnotationInControllerNamespaceController::class . '::show' => $auditorOnly],
            ],
            'own annotation of the application, relative to the namespace of the controller' => [
                OwnAnnotationRelativeController::class,
                [OwnAnnotationRelativeController::class . '::show' => $auditorOnly],
            ],
            'own annotation of the application, through a namespace alias' => [
                OwnAnnotationThroughNamespaceAliasController::class,
                [OwnAnnotationThroughNamespaceAliasController::class . '::show' => $auditorOnly],
            ],
            'own annotation of the application, fully qualified' => [
                OwnAnnotationFullyQualifiedController::class,
                [OwnAnnotationFullyQualifiedController::class . '::show' => $auditorOnly],
            ],
            'own annotation of the application through a class_alias()' => [
                ClassAliasedOwnAnnotationController::class,
                [ClassAliasedOwnAnnotationController::class . '::show' => $auditorOnly],
            ],
            'namespace alias followed by a dash in prose, which Doctrine\'s parser discards' => [
                DashAfterAliasProseController::class,
                [DashAfterAliasProseController::class . '::show' => $adminOnly],
            ],
            'namespace alias in braces in prose, which Doctrine\'s parser skips' => [
                BracedAliasProseController::class,
                [BracedAliasProseController::class . '::show' => $adminOnly],
            ],
            'tag of another library with a colon in its name next to an annotation of the bundle' => [
                ColonInOtherTagController::class,
                [ColonInOtherTagController::class . '::show' => $adminOnly],
            ],
            '@see naming an annotation class after a space, next to an annotation of the bundle' => [
                SeeTagBeforeAnnotationClassController::class,
                [SeeTagBeforeAnnotationClassController::class . '::show' => $adminOnly],
            ],
            'tag of the bundle followed directly by a dash, which Doctrine\'s parser discards' => [
                DashedBundleTagController::class,
                [DashedBundleTagController::class . '::show' => $adminOnly],
            ],
            'annotation of the bundle on the class, fully qualified, with a constant of an imported class' => [
                ClassAnnotationWithImportedConstantController::class,
                [ClassAnnotationWithImportedConstantController::class . '::show' => $adminOnly],
            ],
            '@IgnoreAnnotation with an unquoted name on the class' => [
                UnquotedIgnoreAnnotationController::class,
                [UnquotedIgnoreAnnotationController::class . '::show' => $adminOnly],
            ],
            'imported class that is not an annotation, named by @IgnoreAnnotation, with a tag in its parentheses' => [
                IgnoredNonAnnotationClassController::class,
                [IgnoredNonAnnotationClassController::class . '::show' => $adminOnly],
            ],
            'namespace alias of the bundle with a space after the backslash' => [
                SpaceAfterBackslashAliasController::class,
                [SpaceAfterBackslashAliasController::class . '::show' => $adminOnly],
            ],
            'an @ followed by a space in prose, which Doctrine\'s parser does not read as a tag' => [
                SpaceAfterAtController::class,
                [SpaceAfterAtController::class . '::show' => $adminOnly],
            ],
            'tag of another library in the parentheses of an ignored tag, which Doctrine\'s parser skips' => [
                DeprecatedParenthesisController::class,
                [DeprecatedParenthesisController::class . '::show' => $adminOnly],
            ],
            'namespace alias that nothing imports in the parentheses of an ignored tag' => [
                TodoParenthesisAliasController::class,
                [TodoParenthesisAliasController::class . '::show' => $adminOnly],
            ],
            'tag of the bundle in the parentheses of @see' => [
                SeeParenthesisAliasController::class,
                [SeeParenthesisAliasController::class . '::show' => $adminOnly],
            ],
            'own annotation followed by a line that starts with a class name' => [
                OwnAnnotationThenClassNameLineController::class,
                [OwnAnnotationThenClassNameLineController::class . '::show' => $auditorOnly],
            ],
            'tag named by @IgnoreAnnotation followed by a class name after a space' => [
                IgnoredMixinBeforeAnnotationClassController::class,
                [IgnoredMixinBeforeAnnotationClassController::class . '::show' => $adminOnly],
            ],
            'namespace alias that nothing imports in braces after an annotation\'s parentheses' => [
                BracedAliasAfterAnnotationController::class,
                [BracedAliasAfterAnnotationController::class . '::show' => $adminOnly],
            ],
            'tag of another library through an alias nothing imports: ignored, Doctrine\'s reader fails' => [
                UnimportedForeignNamespaceAliasController::class,
                [UnimportedForeignNamespaceAliasController::class . '::show' => $adminOnly],
            ],
            'annotation of another library relative to the controller namespace, named like one of the bundle' => [
                RelativeForeignAnnotationController::class,
                [RelativeForeignAnnotationController::class . '::show' => $adminOnly],
            ],
            'a double quote in the summary does not hide the tags after it' => [
                QuoteBeforeOwnAnnotationController::class,
                [QuoteBeforeOwnAnnotationController::class . '::show' => $auditorOnly],
            ],
            'a tag name inside quotes does not stop a class name in the arguments from resolving' => [
                QuotedTagNameAndReferenceController::class,
                [QuotedTagNameAndReferenceController::class . '::show' => $adminOnly],
            ],
            'an @ inside prose is not a tag, as Doctrine\'s lexer reads it' => [
                ProseWithAliasTagController::class,
                [ProseWithAliasTagController::class . '::show' => $adminOnly],
            ],
            'annotation of the bundle by its full name without the leading backslash' => [
                UnqualifiedGlobalAnnotationController::class,
                [UnqualifiedGlobalAnnotationController::class . '::show' => $adminOnly],
            ],
            'constant of an imported annotation class of the application in the arguments' => [
                AnnotationClassConstantController::class,
                [AnnotationClassConstantController::class . '::show' => $adminOnly],
            ],
            'constant of an imported annotation class of another library in the arguments' => [
                ForeignAnnotationClassConstantController::class,
                [ForeignAnnotationClassConstantController::class . '::show' => $adminOnly],
            ],
            'annotation class of another library also written as a tag: tag ignored, class name kept as written' => [
                ForeignAnnotationTagAndClassNameController::class,
                [
                    ForeignAnnotationTagAndClassNameController::class . '::show' => (new RestRequestOptions())
                        ->setResponseNormalizationType('LibraryQuery'),
                ],
            ],
            'class name of an imported annotation class in the arguments' => [
                AnnotationClassNameController::class,
                [
                    AnnotationClassNameController::class . '::show' => (new RestRequestOptions())
                        ->setResponseNormalizationType(AuditAccess::class),
                ],
            ],
            'annotation of the bundle next to tags that Doctrine rejects' => [
                BundleAnnotationNextToUnrelatedTagsController::class,
                [BundleAnnotationNextToUnrelatedTagsController::class . '::show' => $adminOnly],
            ],
            'annotation on a method of the file that has no route' => [
                BundleAnnotationOnUnroutedMethodController::class,
                [BundleAnnotationOnUnroutedMethodController::class . '::show' => null],
            ],
            'own action of a class whose trait has an annotated method without a route' => [
                OwnActionUsingAnnotatedTraitController::class,
                [OwnActionUsingAnnotatedTraitController::class . '::show' => null],
            ],
            'annotation of another library with the same short name' => [
                OtherLibraryDocblockController::class,
                [OtherLibraryDocblockController::class . '::show' => null],
            ],
            'no annotation of the bundle' => [
                NoBundleAnnotationController::class,
                [NoBundleAnnotationController::class . '::show' => null],
            ],
            'only @param, @return and @throws' => [
                TagsOnlyDocblockController::class,
                [TagsOnlyDocblockController::class . '::show' => null],
            ],
            'imported but unused annotation of the bundle' => [
                UnusedBundleImportController::class,
                [UnusedBundleImportController::class . '::show' => null],
            ],
            'unknown tags and an annotation of another library that Doctrine rejects' => [
                UnrelatedDocblockTagsController::class,
                [UnrelatedDocblockTagsController::class . '::show' => null],
            ],
            'annotation of another library from the namespace of the controller' => [
                SameNamespaceAnnotationController::class,
                [SameNamespaceAnnotationController::class . '::show' => null],
            ],
        ];
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderMiscasedNameOfLoadedAnnotationDataProvider
     */
    public function testLoadWithoutAnnotationReaderResolvesMiscasedNameOfLoadedAnnotation(
        string $annotation,
        string $controller,
        string $permission
    ): void {
        class_exists($annotation);

        $this->assertEquals(
            [$controller . '::show' => (new RestRequestOptions())->setRequiredPermissions([$permission])],
            $this->loadOptions($this->createLoader(), $controller)
        );
    }

    public static function loadWithoutAnnotationReaderMiscasedNameOfLoadedAnnotationDataProvider(): array
    {
        return [
            'annotation of the bundle imported in another letter case' => [
                RequiredPermissions::class,
                MiscasedImportDocblockController::class,
                'ROLE_ADMIN',
            ],
            'namespace alias of the bundle imported in another letter case' => [
                RequiredPermissions::class,
                MiscasedAliasImportController::class,
                'ROLE_ADMIN',
            ],
            'own annotation through a namespace alias imported in another letter case' => [
                AuditAccess::class,
                MiscasedOwnAliasImportController::class,
                'ROLE_AUDITOR',
            ],
            'fully qualified tag of the bundle in another letter case' => [
                RequiredPermissions::class,
                FullyQualifiedMiscasedTagController::class,
                'ROLE_ADMIN',
            ],
        ];
    }

    /**
     * @dataProvider loadWithAnnotationReaderDataProvider
     */
    public function testLoadWithAnnotationReader(bool $docblockAnnotationsEnabled, ?RestRequestOptions $expected): void
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
            [NoBundleAnnotationController::class . '::show' => $expected],
            $this->loadOptions($loader, NoBundleAnnotationController::class)
        );
    }

    public static function loadWithAnnotationReaderDataProvider(): array
    {
        return [
            'docblock annotations on' => [true, (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN'])],
            'docblock annotations off' => [false, null],
        ];
    }

    /**
     * @dataProvider loadWithDocblockAnnotationsTurnedOffDataProvider
     * @param array<string, RestRequestOptions|null> $expectedOptions
     */
    public function testLoadWithDocblockAnnotationsTurnedOff(string $controller, array $expectedOptions): void
    {
        $loader = $this->createLoader();
        $loader->setDocblockAnnotationsEnabled(false);

        $this->assertEquals($expectedOptions, $this->loadOptions($loader, $controller));
    }

    public static function loadWithDocblockAnnotationsTurnedOffDataProvider(): array
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
        ];
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderAfterAnotherControllerDataProvider
     * @param array<string, RestRequestOptions|null> $expectedOptions
     */
    public function testLoadWithoutAnnotationReaderAfterAnotherController(
        string $firstController,
        string $controller,
        array $expectedOptions
    ): void {
        $loader = $this->createLoader();
        $loader->load($firstController);

        $this->assertEquals($expectedOptions, $this->loadOptions($loader, $controller));
    }

    public static function loadWithoutAnnotationReaderAfterAnotherControllerDataProvider(): array
    {
        $adminOnly = (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']);

        return [
            'after a controller that imports the annotation without using it' => [
                UnusedBundleImportController::class,
                ClassAliasDocblockController::class,
                [ClassAliasDocblockController::class . '::show' => $adminOnly],
            ],
            'after a controller with an annotation on its class' => [
                ClassAndActionDocblockController::class,
                DocblockOptionsOnAttributeRouteController::class,
                [DocblockOptionsOnAttributeRouteController::class . '::show' => $adminOnly],
            ],
        ];
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderFailureDataProvider
     * @param string[] $loadedControllers
     */
    public function testLoadWithoutAnnotationReaderFails(
        array $loadedControllers,
        string $controller,
        string $exception,
        string $message
    ): void {
        $loader = $this->createLoader();
        foreach ($loadedControllers as $loadedController) {
            $loader->load($loadedController);
        }

        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $loader->load($controller);
    }

    public static function loadWithoutAnnotationReaderFailureDataProvider(): array
    {
        return [
            'un-imported annotation of the bundle' => [
                [],
                UnimportedAnnotationDocblockController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in method '
                . UnimportedAnnotationDocblockController::class . '::show() does not resolve to a class',
            ],
            'annotation of the bundle imported from a namespace that does not exist' => [
                [],
                MisspelledImportDocblockController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in method '
                . MisspelledImportDocblockController::class . '::show() does not resolve to a class',
            ],
            'annotation on an action of the class that only its trait imports' => [
                [],
                OwnActionWithTraitImportController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in method '
                . OwnActionWithTraitImportController::class . '::show() does not resolve to a class',
            ],
            'annotation on the class that only its trait imports' => [
                [],
                ClassDocblockWithTraitImportController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in class '
                . ClassDocblockWithTraitImportController::class . ' does not resolve to a class',
            ],
            'method of a trait whose annotation neither it nor the class imports, after one that imports it' => [
                [ClassImportingForTraitController::class],
                ClassNotImportingForTraitController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in method '
                . ClassNotImportingForTraitController::class . '::index() does not resolve to a class',
            ],
            'namespace alias on the class that only its trait imports' => [
                [],
                ClassAliasImportedByTraitController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in class '
                . ClassAliasImportedByTraitController::class . ' does not resolve to a class',
            ],
            'namespace alias and an un-imported short name of the same annotation' => [
                [],
                AliasAndBareDuplicateController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in method '
                . AliasAndBareDuplicateController::class . '::show() does not resolve to a class',
            ],
            'annotation of the bundle imported from a sub-namespace of the bundle that does not exist' => [
                [],
                MissingBundleSubNamespaceImportController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in method '
                . MissingBundleSubNamespaceImportController::class . '::show() does not resolve to a class',
            ],
            'short name of the bundle importing a class that is not an annotation' => [
                [],
                NonAnnotationImportDocblockController::class,
                ConfigurationException::class,
                'The annotation "@Validation" in method ' . NonAnnotationImportDocblockController::class
                . '::show() resolves to ' . Validation::class . ', not to the annotation '
                . ValidationAnnotation::class,
            ],
            'namespace alias of the bundle that nothing imports' => [
                [],
                UnimportedNamespaceAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermissions" in method ' . UnimportedNamespaceAliasController::class
                . '::show() does not resolve to a class, so it would be ignored: import it from '
                . 'Paysera\\Bundle\\ApiBundle\\Annotation',
            ],
            'namespace alias of the bundle that nothing imports, on the class' => [
                [],
                UnimportedNamespaceAliasOnClassController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermissions" in class '
                . UnimportedNamespaceAliasOnClassController::class . ' does not resolve to a class',
            ],
            'namespace alias imported for a namespace without that annotation' => [
                [],
                NamespaceAliasWithoutAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermissions" in method '
                . NamespaceAliasWithoutAnnotationController::class . '::show() does not resolve to a class',
            ],
            'import of a class of the bundle that does not exist' => [
                [],
                MisspelledBundleImportController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermission" in method ' . MisspelledBundleImportController::class
                . '::show() does not resolve to a class, so it would be ignored: import it from '
                . 'Paysera\\Bundle\\ApiBundle\\Annotation',
            ],
            'fully qualified tag naming a class of the bundle that does not exist, with Doctrine\'s error' => [
                [],
                MisspelledFullyQualifiedController::class,
                AnnotationException::class,
                'The annotation "@Paysera\\Bundle\\ApiBundle\\Annotation\\RequiredPermission" in method '
                . MisspelledFullyQualifiedController::class . '::show() does not exist, or could not be auto-loaded.',
            ],
            'namespace alias of the bundle naming a class that does not exist' => [
                [],
                MisspelledBundleAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermission" in method '
                . MisspelledBundleAliasController::class . '::show() does not resolve to a class',
            ],
            'IgnoreAnnotation does not exempt an annotation of the bundle that is not imported, the load fails' => [
                [],
                IgnoredUnimportedAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions" in method '
                . IgnoredUnimportedAnnotationController::class . '::show() does not resolve to a class',
            ],
            'namespace alias of the bundle in another letter case, naming a class that does not exist' => [
                [],
                MiscasedAliasMisspelledController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermission" in method '
                . MiscasedAliasMisspelledController::class . '::show() does not resolve to a class',
            ],
            'namespace alias that nothing imports, after a double quote in the summary' => [
                [],
                QuoteBeforeAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermissions" in method '
                . QuoteBeforeAliasController::class . '::show() does not resolve to a class',
            ],
            'annotation of the bundle by its full name, the first name in another letter case' => [
                [],
                MiscasedFirstNameController::class,
                ConfigurationException::class,
                'The annotation "@paysera\\Bundle\\ApiBundle\\Annotation\\RequiredPermissions" in method '
                . MiscasedFirstNameController::class . '::show() is written in another letter case: write it as '
                . RequiredPermissions::class,
            ],
            'namespace alias that nothing imports, split across lines' => [
                [],
                UnimportedSplitAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermissions" in method '
                . UnimportedSplitAliasController::class . '::show() does not resolve to a class',
            ],
            'namespace alias that nothing imports, with a space after the backslash' => [
                [],
                UnimportedSpacedAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermissions" in method '
                . UnimportedSpacedAliasController::class . '::show() does not resolve to a class',
            ],
            'namespace alias of the bundle naming a class that does not exist, split across lines' => [
                [],
                SplitMisspelledAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermission" in method '
                . SplitMisspelledAliasController::class . '::show() does not resolve to a class',
            ],
            'namespace alias that nothing imports, continued on the next line with another backslash' => [
                [],
                SplitBackslashUnimportedAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\\\RequiredPermissions" in method '
                . SplitBackslashUnimportedAliasController::class
                . '::show() is written with a doubled backslash: write one',
            ],
            'own annotation through a namespace alias, continued on the next line with another backslash' => [
                [],
                SplitBackslashOwnAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@Own\\\\AuditAccess" in method ' . SplitBackslashOwnAnnotationController::class
                . '::show() is written with a doubled backslash: write one',
            ],
            'namespace alias of the bundle with a colon in the name, which Doctrine\'s lexer reads as part of it' => [
                [],
                ColonInTagNameController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\RequiredPermissions:admin" in method ' . ColonInTagNameController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'imported short name of the bundle with a colon in the name' => [
                [],
                BareColonInTagNameController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions:admin" in method ' . BareColonInTagNameController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'short name of the bundle with a colon in the name, not imported' => [
                [],
                UnimportedBareColonController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions:admin" in method ' . UnimportedBareColonController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'namespace alias of the bundle with a space before the backslash' => [
                [],
                SpaceBeforeBackslashController::class,
                ConfigurationException::class,
                'The annotation "@Rest \\RequiredPermissions" in method ' . SpaceBeforeBackslashController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'namespace alias imported with a leading backslash, with a space before the backslash' => [
                [],
                LeadingBackslashSpacedAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest \\RequiredPermissions" in method ' . LeadingBackslashSpacedAliasController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'parent namespace alias imported with a leading backslash, with a space before the backslash' => [
                [],
                LeadingBackslashSpacedParentAliasController::class,
                ConfigurationException::class,
                'The annotation "@Api\\Annotation \\RequiredPermissions" in method '
                . LeadingBackslashSpacedParentAliasController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'namespace alias that nothing imports, with a space before the backslash' => [
                [],
                SpacedUnimportedAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest \\RequiredPermissions" in method ' . SpacedUnimportedAliasController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'namespace alias of another library, with a space before the backslash' => [
                [],
                SpacedForeignAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest \\RequiredPermissions" in method ' . SpacedForeignAliasController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'namespace alias that nothing imports, with whitespace around a lone backslash' => [
                [],
                SpacedBackslashUnimportedAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest \\RequiredPermissions" in method '
                . SpacedBackslashUnimportedAliasController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'own annotation through a namespace alias, with a space and a doubled backslash' => [
                [],
                SpacedDoubleBackslashOwnAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@Own \\\\AuditAccess" in method ' . SpacedDoubleBackslashOwnAnnotationController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'own annotation through a namespace alias with a colon after the backslash' => [
                [],
                ColonAfterBackslashOwnAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@Own\\:AuditAccess" in method ' . ColonAfterBackslashOwnAnnotationController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'namespace alias of the bundle with a space instead of the backslash' => [
                [],
                SpaceInsteadOfBackslashController::class,
                ConfigurationException::class,
                'The annotation "@Rest" in method '
                . SpaceInsteadOfBackslashController::class
                . '::show() names the namespace Paysera\\Bundle\\ApiBundle\\Annotation,'
                . ' not one of its annotations',
            ],
            'namespace alias imported with a leading backslash, with a space instead of the backslash' => [
                [],
                LeadingBackslashSpaceNotBackslashController::class,
                ConfigurationException::class,
                'The annotation "@Rest" in method '
                . LeadingBackslashSpaceNotBackslashController::class
                . '::show() names the namespace Paysera\\Bundle\\ApiBundle\\Annotation,'
                . ' not one of its annotations',
            ],
            'annotation namespace of the bundle written in full without an import, with a space after it' => [
                [],
                UnimportedNamespaceSpaceController::class,
                ConfigurationException::class,
                'The annotation "@Paysera\\Bundle\\ApiBundle\\Annotation" in method '
                . UnimportedNamespaceSpaceController::class
                . '::show() names the namespace Paysera\\Bundle\\ApiBundle\\Annotation,'
                . ' not one of its annotations',
            ],
            'imported namespace alias named by @IgnoreAnnotation, with a space before the backslash' => [
                [],
                IgnoredImportedAliasSpacedController::class,
                ConfigurationException::class,
                'The annotation "@Api \\Annotation\\RequiredPermissions" in method '
                . IgnoredImportedAliasSpacedController::class . '::show() has whitespace inside its name: remove it',
            ],
            'namespace alias of the bundle with a dot instead of the backslash' => [
                [],
                DotInsteadOfBackslashController::class,
                ConfigurationException::class,
                'The annotation "@Rest" in method '
                . DotInsteadOfBackslashController::class
                . '::show() names the namespace Paysera\\Bundle\\ApiBundle\\Annotation,'
                . ' not one of its annotations',
            ],
            'namespace alias of the bundle with a slash instead of the backslash' => [
                [],
                SlashInsteadOfBackslashController::class,
                ConfigurationException::class,
                'The annotation "@Rest" in method '
                . SlashInsteadOfBackslashController::class
                . '::show() names the namespace Paysera\\Bundle\\ApiBundle\\Annotation,'
                . ' not one of its annotations',
            ],
            'parent namespace alias naming the annotation namespace, with a space before the short name' => [
                [],
                SpaceAfterParentAliasPathController::class,
                ConfigurationException::class,
                'The annotation "@Api\\Annotation" in method '
                . SpaceAfterParentAliasPathController::class
                . '::show() names the namespace Paysera\\Bundle\\ApiBundle\\Annotation,'
                . ' not one of its annotations',
            ],
            'unresolved tag of the bundle as the first value of an annotation' => [
                [],
                NestedAfterParenthesisController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\Validation" in method '
                . NestedAfterParenthesisController::class . '::show() does not resolve to a class',
            ],
            'unresolved tag of the bundle as a value after a comma' => [
                [],
                NestedAfterCommaController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\Validation" in method '
                . NestedAfterCommaController::class . '::show() does not resolve to a class',
            ],
            'unresolved tag of the bundle as an array value' => [
                [],
                NestedInBracesController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\Validation" in method '
                . NestedInBracesController::class . '::show() does not resolve to a class',
            ],
            'unresolved tag of the bundle as an array value after a key and a colon' => [
                [],
                NestedAfterColonController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\Validation" in method '
                . NestedAfterColonController::class . '::show() does not resolve to a class',
            ],
            'tag of the bundle with a doubled backslash as the value of an annotation' => [
                [],
                NestedDoubledBackslashController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\\\Validation" in method '
                . NestedDoubledBackslashController::class . '::show() is written with a doubled backslash: write one',
            ],
            'namespace alias that nothing imports, the name continued on the next line after a line break' => [
                [],
                NewlineUnimportedAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest \\RequiredPermissions" in method ' . NewlineUnimportedAliasController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'own annotation through a namespace alias, with a space before the backslash' => [
                [],
                SpacedOwnAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@Own \\AuditAccess" in method ' . SpacedOwnAnnotationController::class
                . '::show() has whitespace inside its name: remove it',
            ],
            'colon right after a namespace alias of the bundle' => [
                [],
                ColonAfterAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest:RequiredPermissions" in method ' . ColonAfterAliasController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'double colon right after a namespace alias of the bundle' => [
                [],
                DoubleColonAfterAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest::RequiredPermissions" in method ' . DoubleColonAfterAliasController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'own annotation through a namespace alias with a colon in its name' => [
                [],
                ColonInOwnAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@Own\\AuditAccess:strict" in method ' . ColonInOwnAnnotationController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'imported own annotation with a colon in its name' => [
                [],
                ColonInImportedOwnAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@AuditAccess:strict" in method ' . ColonInImportedOwnAnnotationController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'imported short name of the bundle with a colon before a backslash' => [
                [],
                ColonBeforeBackslashController::class,
                ConfigurationException::class,
                'The annotation "@RequiredPermissions:\\Admin" in method ' . ColonBeforeBackslashController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'namespace alias that nothing imports, with a colon before the backslash' => [
                [],
                UnimportedColonBeforeBackslashController::class,
                ConfigurationException::class,
                'The annotation "@Rest:\\RequiredPermissions" in method '
                . UnimportedColonBeforeBackslashController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'class alias of an annotation of the bundle with a colon in the name' => [
                [],
                ColonAfterClassAliasController::class,
                ConfigurationException::class,
                'The annotation "@Perm:admin" in method ' . ColonAfterClassAliasController::class
                . '::show() does not resolve to a class, so it would be ignored: a class name has no colon',
            ],
            'annotation of the bundle written through its alias in another letter case' => [
                [],
                MiscasedAliasTagController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\requiredPermissions" in method ' . MiscasedAliasTagController::class
                . '::show() is written in another letter case: write it as ' . RequiredPermissions::class,
            ],
            'two tags with the same first name resolving to different namespaces' => [
                [],
                FirstNameSharedController::class,
                ConfigurationException::class,
                'The annotations "@Paysera\\Audited" and '
                . '"@Paysera\\Bundle\\ApiBundle\\Annotation\\RequiredPermissions" in method '
                . FirstNameSharedController::class . '::show() start with the same name but resolve in '
                . 'different namespaces: write one of them fully qualified',
            ],
            'nested annotation that is not imported, next to a namespace alias named like an annotation' => [
                [],
                NestedBlameController::class,
                ConfigurationException::class,
                'The annotation "@Validation" in method ' . NestedBlameController::class
                . '::show() does not resolve to a class, so it would be ignored: import ' . ValidationAnnotation::class,
            ],
            'nested annotation of the bundle that is not imported' => [
                [],
                NestedUnimportedAnnotationController::class,
                ConfigurationException::class,
                'The annotation "@Validation" in method ' . NestedUnimportedAnnotationController::class
                . '::show() does not resolve to a class, so it would be ignored: import ' . ValidationAnnotation::class,
            ],
            'nested annotation through a namespace alias that nothing imports' => [
                [],
                NestedUnimportedAliasController::class,
                ConfigurationException::class,
                'The annotation "@Rest\\Validation" in method '
                . NestedUnimportedAliasController::class . '::show() does not resolve to a class',
            ],
            'syntax error next to an annotation of the bundle that is not imported: Doctrine\'s error is kept' => [
                [],
                SyntaxErrorNextToUnimportedController::class,
                AnnotationException::class,
                '[Syntax Error]',
            ],
            'annotation of the bundle on a target it does not allow' => [
                [],
                MethodAnnotationOnClassController::class,
                AnnotationException::class,
                'Annotation @Query is not allowed to be declared on class ' . MethodAnnotationOnClassController::class,
            ],
            'fully qualified name of a class that does not exist' => [
                [],
                FullyQualifiedMissingClassController::class,
                AnnotationException::class,
                'The annotation "@Missing\\Library\\Cache" in method '
                . FullyQualifiedMissingClassController::class . '::show() does not exist',
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
        array $arguments = []
    ): void {
        $lines = $this->runFixtureScript($ini, 'load-routes.php', $arguments);
        $output = (string)end($lines);
        if ($output === 'no opcache') {
            $this->markTestSkipped('This PHP has no OPcache to strip docblocks');
        }

        $this->assertSame($expectedOutput, $output, implode("\n", $lines));
    }

    /**
     * @dataProvider loadWithoutAnnotationReaderInFreshProcessDataProvider
     */
    public function testLoadWithoutAnnotationReaderInFreshProcess(string $controller, string $expectedOutput): void
    {
        if (file_exists(strtoupper(__FILE__)) && file_exists(strtolower(__FILE__))) {
            $this->markTestSkipped('On a case-insensitive filesystem a miscased import autoloads');
        }

        $lines = $this->runFixtureScript([], 'load-controller.php', [$controller]);

        $this->assertSame($expectedOutput, (string)end($lines), implode("\n", $lines));
    }

    public static function loadWithoutAnnotationReaderInFreshProcessDataProvider(): array
    {
        return [
            'tag of the bundle written with a doubled backslash, which Doctrine\'s parser cannot load' => [
                DoubledBackslashAliasController::class,
                ConfigurationException::class . ': The annotation "@Rest\\\\RequiredPermissions" in method '
                . DoubledBackslashAliasController::class . '::show() is written with a doubled backslash: write one',
            ],
            'annotation of the bundle through its alias in another letter case, not loaded yet' => [
                MiscasedAliasTagController::class,
                ConfigurationException::class . ': The annotation "@Rest\\requiredPermissions" in method '
                . MiscasedAliasTagController::class . '::show() is written in another letter case: write it as '
                . RequiredPermissions::class,
            ],
            'own annotation written with a doubled backslash, which Doctrine\'s parser cannot load' => [
                OwnAnnotationDoubledBackslashController::class,
                ConfigurationException::class . ': The annotation "@Own\\\\AuditAccess" in method '
                . OwnAnnotationDoubledBackslashController::class
                . '::show() is written with a doubled backslash: write one',
            ],
            'fully qualified tag of the bundle with a doubled backslash, after the class is loaded' => [
                FullyQualifiedDoubledBackslashController::class,
                ConfigurationException::class . ': The annotation "@\\Paysera\\Bundle\\ApiBundle\\Annotation\\\\'
                . 'RequiredPermissions" in method ' . FullyQualifiedDoubledBackslashController::class
                . '::show() is written with a doubled backslash: write one',
            ],
            'miscased import of an annotation of the bundle that the process has not loaded yet' => [
                MiscasedImportDocblockController::class,
                ConfigurationException::class . ': The annotation "@RequiredPermissions" in method '
                . MiscasedImportDocblockController::class . '::show() is imported in another letter case '
                . '(Paysera\\Bundle\\ApiBundle\\annotation\\RequiredPermissions): import '
                . RequiredPermissions::class . ' so it autoloads',
            ],
            'miscased import of an annotation of the bundle next to a tag Doctrine rejects, not loaded yet' => [
                MiscasedImportNextToRejectedTagController::class,
                ConfigurationException::class . ': The annotation "@RequiredPermissions" in method '
                . MiscasedImportNextToRejectedTagController::class . '::show() is imported in another letter case '
                . '(Paysera\\Bundle\\ApiBundle\\annotation\\RequiredPermissions): import '
                . RequiredPermissions::class . ' so it autoloads',
            ],
            'namespace alias of the bundle imported in another letter case, not loaded yet' => [
                MiscasedAliasImportController::class,
                ConfigurationException::class . ': The annotation "@Rest\\RequiredPermissions" in method '
                . MiscasedAliasImportController::class . '::show() is imported in another letter case '
                . '(Paysera\\Bundle\\ApiBundle\\annotation): import Paysera\\Bundle\\ApiBundle\\Annotation'
                . ' so it autoloads',
            ],
            'annotation of the bundle imported with a leading backslash in another letter case, not loaded yet' => [
                LeadingBackslashMiscasedImportController::class,
                ConfigurationException::class . ': The annotation "@RequiredPermissions" in method '
                . LeadingBackslashMiscasedImportController::class . '::show() is imported in another letter case '
                . '(\\Paysera\\Bundle\\ApiBundle\\annotation\\RequiredPermissions): import '
                . RequiredPermissions::class . ' so it autoloads',
            ],
            'fully qualified tag of the bundle in another letter case, not loaded yet: Doctrine\'s error' => [
                FullyQualifiedMiscasedTagController::class,
                AnnotationException::class . ': [Semantical Error] The annotation '
                . '"@Paysera\\Bundle\\ApiBundle\\Annotation\\requiredPermissions" in method '
                . FullyQualifiedMiscasedTagController::class . '::show() does not exist, or could not be auto-loaded.',
            ],
            'namespace alias of the bundle continued on the next line with another backslash' => [
                SplitBackslashAliasController::class,
                ConfigurationException::class . ': The annotation "@Rest\\\\RequiredPermissions" in method '
                . SplitBackslashAliasController::class . '::show() is written with a doubled backslash: write one',
            ],
        ];
    }

    public static function loadWithoutAnnotationReaderUnderOpcacheDataProvider(): array
    {
        return [
            'docblocks stripped' => [
                [
                    '-d',
                    'opcache.enable_cli=1',
                    '-d',
                    'opcache.save_comments=0',
                    '-d',
                    'opcache.file_update_protection=0',
                ],
                ConfigurationException::class,
            ],
            'docblocks stripped with docblock annotations turned off' => [
                [
                    '-d',
                    'opcache.enable_cli=1',
                    '-d',
                    'opcache.save_comments=0',
                    '-d',
                    'opcache.file_update_protection=0',
                ],
                'null',
                ['attributes-only'],
            ],
            'docblocks kept' => [
                [
                    '-d',
                    'opcache.enable_cli=1',
                    '-d',
                    'opcache.save_comments=1',
                    '-d',
                    'opcache.file_update_protection=0',
                ],
                '["ROLE_ADMIN"]',
            ],
            'save_comments off with OPcache off for the command line' => [
                ['-d', 'opcache.enable_cli=0', '-d', 'opcache.save_comments=0'],
                '["ROLE_ADMIN"]',
            ],
            'save_comments off with OPcache disabled' => [
                ['-d', 'opcache.enable=0', '-d', 'opcache.enable_cli=1', '-d', 'opcache.save_comments=0'],
                '["ROLE_ADMIN"]',
            ],
            'save_comments off while OPcache leaves recently changed files uncached' => [
                [
                    '-d',
                    'opcache.enable_cli=1',
                    '-d',
                    'opcache.save_comments=0',
                    '-d',
                    'opcache.file_update_protection=2000000000',
                ],
                ConfigurationException::class,
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
