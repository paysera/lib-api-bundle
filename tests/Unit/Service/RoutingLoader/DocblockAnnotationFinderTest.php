<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

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
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ControllerUsingTheTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CustomAnnotationOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CustomRestAnnotation;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DirectImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\GroupImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NotTopLevelAnnotationsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SameLineImportController;
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

        $this->assertSame(
            $expectedAnnotations,
            $finder->findBundleAnnotations(new ReflectionClass($className), new ReflectionMethod($className, $methodName))
        );
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
            'only top-level annotations count, as Doctrine reads them' => [
                NotTopLevelAnnotationsController::class,
                'find',
                [Query::class],
            ],
            'an application\'s own annotation class in the same namespace' => [
                CustomAnnotationOnAttributeRouteController::class,
                'show',
                [CustomRestAnnotation::class],
            ],
        ];
    }
}
