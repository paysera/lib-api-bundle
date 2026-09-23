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
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DirectImportController;
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
        ];
    }
}
