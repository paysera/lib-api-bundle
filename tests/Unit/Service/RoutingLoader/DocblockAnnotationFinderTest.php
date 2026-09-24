<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use ArrayObject;
use Paysera\Bundle\ApiBundle\Annotation\Body;
use Paysera\Bundle\ApiBundle\Annotation\PathAttribute;
use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Annotation\Validation;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationFinder;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ArgumentBoundariesController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOnlyController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildOfAParentInAnotherNamespace;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildWithoutImports;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildWithOwnImports;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassLevelCustomAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CommaImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CommentedImportsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ControllerOverridingTheTraitMethod;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ControllerUsingTheTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CustomAnnotationOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CustomRestAnnotation;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DirectImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Directory;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FunctionAndConstantImportsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\GroupImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\IgnoredTagNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ImportListWithAliasesController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ImportOnTheClassLineController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ImportsBeforeTheClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NameAcrossASeparatorController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NonAnnotationClassTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NotTopLevelAnnotationsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherNamespace\LocalRestAnnotation;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\QualifiedNameController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SameLineImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\StarAndQuoteBeforeAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TwoImportsOnOneLineController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TwoNamespacesController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\WhitespaceBeforeAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\WrongCaseNameController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class DocblockAnnotationFinderTest extends TestCase
{
    /**
     * @dataProvider controllerDataProvider
     *
     * @param string[] $expectedAnnotations
     */
    public function testFindsTheBundleAnnotationsOfAMethodAndItsClass(
        string $className,
        string $methodName,
        array $expectedAnnotations
    ) {
        $finder = new DocblockAnnotationFinder();

        $annotations = $finder->findBundleAnnotations(
            new ReflectionClass($className),
            new ReflectionMethod($className, $methodName)
        );

        $this->assertSame($expectedAnnotations, $annotations);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testResolvesABundleAnnotationWrittenInAnotherCaseBeforeAnythingLoadedIt()
    {
        $finder = new DocblockAnnotationFinder();

        $annotations = $finder->findBundleAnnotations(
            new ReflectionClass(WrongCaseNameController::class),
            new ReflectionMethod(WrongCaseNameController::class, 'show')
        );

        $this->assertSame([RequiredPermissions::class], $annotations);
    }

    public function testReadsAClassDeclaredInEvaluatedCode()
    {
        $testDouble = get_class($this->createMock(DirectImportController::class));
        $finder = new DocblockAnnotationFinder();

        $annotations = $finder->findBundleAnnotations(
            new ReflectionClass($testDouble),
            new ReflectionMethod($testDouble, 'create')
        );

        $this->assertSame([], $annotations);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string[]}>
     */
    public static function controllerDataProvider(): array
    {
        return [
            'class imports, class and method docblocks, each annotation once' => [
                DirectImportController::class,
                'create',
                [RequiredPermissions::class, Body::class],
            ],
            'namespace alias, class alias and a fully qualified name' => [
                AliasedImportController::class,
                'find',
                [Query::class, Validation::class, PathAttribute::class, ResponseNormalization::class],
            ],
            'attributes, Symfony tags and attribute class names are not the annotations' => [
                AttributeOnlyController::class,
                'create',
                [],
            ],
            'a method declared in a parent resolves through the parent file imports' => [
                ChildWithoutImports::class,
                'inherited',
                [ResponseNormalization::class],
            ],
            'a method the class declares over its trait\'s resolves through the class file imports only' => [
                ControllerOverridingTheTraitMethod::class,
                'find',
                [Query::class],
            ],
            'the class docblock resolves through its own file, the inherited method through the parent\'s' => [
                ChildWithOwnImports::class,
                'inherited',
                [RequiredPermissions::class, ResponseNormalization::class],
            ],
            'a class docblock resolves relative to its namespace' => [
                ClassLevelCustomAnnotationController::class,
                'show',
                [CustomRestAnnotation::class],
            ],
            'a name resolves in the namespace before a PHP class of the same name' => [
                ClassLevelCustomAnnotationController::class,
                'list',
                [CustomRestAnnotation::class, Directory::class],
            ],
            'each of two classes in one file reads its own namespace block' => [
                ChildOfAParentInAnotherNamespace::class,
                'create',
                [RequiredPermissions::class, CustomRestAnnotation::class, Body::class, LocalRestAnnotation::class],
            ],
            'an import on the class\'s own line counts' => [
                ImportOnTheClassLineController::class,
                'find',
                [Query::class],
            ],
            'a method from a trait resolves through the trait file imports' => [
                ControllerUsingTheTrait::class,
                'fromTrait',
                [Query::class],
            ],
            'a group import with an alias' => [
                GroupImportController::class,
                'create',
                [RequiredPermissions::class, Body::class],
            ],
            'a comma-separated import over two lines' => [
                CommaImportController::class,
                'create',
                [RequiredPermissions::class, Body::class],
            ],
            'an import on the namespace line, and a full name without the leading backslash' => [
                SameLineImportController::class,
                'create',
                [Body::class, RequiredPermissions::class],
            ],
            'comments inside use statements' => [
                CommentedImportsController::class,
                'find',
                [Query::class, RequiredPermissions::class, Validation::class],
            ],
            'two use statements on one line' => [
                TwoImportsOnOneLineController::class,
                'create',
                [Body::class, RequiredPermissions::class],
            ],
            'only the imports above the class count: not a commented-out one, not a trait in the class body' => [
                ImportsBeforeTheClassController::class,
                'find',
                [Query::class, RequiredPermissions::class],
            ],
            'a function or constant import of the same name does not replace a class import' => [
                FunctionAndConstantImportsController::class,
                'show',
                [RequiredPermissions::class],
            ],
            'an import in another namespace block of the file does not count' => [
                TwoNamespacesController::class,
                'show',
                [CustomRestAnnotation::class],
            ],
            'an "@" right after a word or "{" does not start an annotation, and a nested one is part of its parent' => [
                NotTopLevelAnnotationsController::class,
                'find',
                [Query::class],
            ],
            'a nested annotation after a space is part of its parent too' => [
                NotTopLevelAnnotationsController::class,
                'findWithASpaceBeforeTheNestedAnnotation',
                [Query::class],
            ],
            'reading starts at the first "@" after a space, a tab or "*", as in Doctrine' => [
                NotTopLevelAnnotationsController::class,
                'annotationAtTheStartOfALine',
                [],
            ],
            'an annotation at the start of a line is skipped before the first one Doctrine reads' => [
                NotTopLevelAnnotationsController::class,
                'annotationAfterOneAtTheStartOfALine',
                [Query::class],
            ],
            'an "@" inside a quoted string is text' => [
                NotTopLevelAnnotationsController::class,
                'annotationInAString',
                [],
            ],
            'a name followed by "-" is not an annotation' => [
                NotTopLevelAnnotationsController::class,
                'annotationFollowedByADash',
                [],
            ],
            'the parentheses after a class that is not an annotation are read, as in Doctrine' => [
                NonAnnotationClassTagController::class,
                'show',
                [RequiredPermissions::class],
            ],
            'a name with a namespace keeps its arguments, as Doctrine never ignores it' => [
                QualifiedNameController::class,
                'withoutTheLeadingBackslash',
                [Query::class],
            ],
            'a fully qualified name keeps its arguments' => [
                QualifiedNameController::class,
                'withTheLeadingBackslash',
                [Query::class],
            ],
            'two leading backslashes, as Doctrine strips them all' => [
                QualifiedNameController::class,
                'withTwoLeadingBackslashes',
                [RequiredPermissions::class],
            ],
            'a name continues after a separator and a space, as Doctrine joins it' => [
                NameAcrossASeparatorController::class,
                'spaceAfterTheSeparator',
                [RequiredPermissions::class],
            ],
            'a name continues after a separator and a line break' => [
                NameAcrossASeparatorController::class,
                'lineBreakAfterTheSeparator',
                [RequiredPermissions::class],
            ],
            'a name continues after a separator and a star' => [
                NameAcrossASeparatorController::class,
                'starAfterTheSeparator',
                [RequiredPermissions::class],
            ],
            'a class named like a tag Doctrine ignores does not hide what its parentheses hold' => [
                IgnoredTagNameController::class,
                'show',
                [RequiredPermissions::class],
            ],
            'an annotation without arguments does not take the next one\'s' => [
                ArgumentBoundariesController::class,
                'afterAnAnnotationWithoutArguments',
                [ResponseNormalization::class, RequiredPermissions::class],
            ],
            'a parenthesis in a quoted argument does not end the arguments' => [
                ArgumentBoundariesController::class,
                'afterAParenthesisInAString',
                [Query::class, RequiredPermissions::class],
            ],
            'arguments after a space are still the annotation\'s' => [
                ArgumentBoundariesController::class,
                'argumentsAfterASpace',
                [Query::class],
            ],
            'a one-letter alias, aliases in a comma list and an import written with a leading backslash' => [
                ImportListWithAliasesController::class,
                'create',
                [RequiredPermissions::class, Body::class, Validation::class, Query::class],
            ],
            'reading can start at an "@" right after a star' => [
                StarAndQuoteBeforeAnnotationController::class,
                'firstRightAfterAStar',
                [RequiredPermissions::class],
            ],
            'an "@" right after a star starts an annotation' => [
                StarAndQuoteBeforeAnnotationController::class,
                'laterAfterAStar',
                [ResponseNormalization::class, RequiredPermissions::class],
            ],
            'reading does not start at an "@" glued to a quote' => [
                StarAndQuoteBeforeAnnotationController::class,
                'firstGluedToAQuote',
                [],
            ],
            'an "@" right after a closing quote starts an annotation, as in Doctrine' => [
                StarAndQuoteBeforeAnnotationController::class,
                'afterAClosingQuote',
                [RequiredPermissions::class],
            ],
            'an "@" right after a lone quote starts one too' => [
                StarAndQuoteBeforeAnnotationController::class,
                'afterALoneQuote',
                [ResponseNormalization::class],
            ],
            'a ":" after a name leaves it an annotation' => [
                ArgumentBoundariesController::class,
                'colonAfterAName',
                [ResponseNormalization::class],
            ],
            'the parentheses after an imported class that is not an annotation are read' => [
                NonAnnotationClassTagController::class,
                'afterAnImportedNonAnnotationClass',
                [RequiredPermissions::class],
            ],
            'a name followed by a negative number is an annotation, as in Doctrine' => [
                ArgumentBoundariesController::class,
                'nameFollowedByANegativeNumber',
                [ResponseNormalization::class],
            ],
            'a no-break space before an annotation is whitespace, as in Doctrine\'s lexer' => [
                WhitespaceBeforeAnnotationController::class,
                'afterANoBreakSpace',
                [RequiredPermissions::class],
            ],
            'reading can start at an "@" after a tab' => [
                WhitespaceBeforeAnnotationController::class,
                'afterATab',
                [RequiredPermissions::class],
            ],
            'a class of PHP itself has no docblocks' => [
                ArrayObject::class,
                'count',
                [],
            ],
            'an application\'s own annotation class in the same namespace' => [
                CustomAnnotationOnAttributeRouteController::class,
                'show',
                [CustomRestAnnotation::class],
            ],
        ];
    }
}
