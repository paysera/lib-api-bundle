<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationReader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitDocblockOptionsController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class DocblockAnnotationReaderTest extends TestCase
{
    public function testReadAnnotationsWithoutReaderReadsTraitAction(): void
    {
        $annotations = (new DocblockAnnotationReader())->readAnnotations(
            new ReflectionClass(TraitDocblockOptionsController::class),
            new ReflectionMethod(TraitDocblockOptionsController::class, 'show'),
            null
        );

        $this->assertEquals([new RequiredPermissions(['permissions' => ['ROLE_ADMIN']])], $annotations);
    }
}
