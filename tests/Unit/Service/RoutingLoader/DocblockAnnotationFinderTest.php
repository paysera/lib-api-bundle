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
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOnlyController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildWithoutImports;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CommaImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CommentedImportsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ControllerUsingTheTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CustomAnnotationOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CustomRestAnnotation;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DirectImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\GroupImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ImportsBeforeTheClassController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NoBreakSpaceController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NotTopLevelAnnotationsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SameLineImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TwoImportsOnOneLineController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TwoNamespacesController;
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
                [Query::class, Validation::class, PathAttribute::class],
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
            'a no-break space before an annotation is whitespace, as in Doctrine\'s lexer' => [
                NoBreakSpaceController::class,
                'show',
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
