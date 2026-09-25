<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\PathAttributeResolver;

use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\PathAttributeResolverInterface;
use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\PathAttributeResolverRegistry;

class PathAttributeResolverRegistryTest extends MockeryTestCase
{
    public function testReturnsTheResolverRegisteredForAType()
    {
        $resolver = Mockery::mock(PathAttributeResolverInterface::class);
        $registry = new PathAttributeResolverRegistry();
        $registry->registerPathAttributeResolver($resolver, 'user');

        $this->assertSame($resolver, $registry->getResolverByType('user'));
    }

    public function testFailsForAnUnregisteredType()
    {
        $registry = new PathAttributeResolverRegistry();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No such path attribute resolver registered: "user"');

        $registry->getResolverByType('user');
    }
}
