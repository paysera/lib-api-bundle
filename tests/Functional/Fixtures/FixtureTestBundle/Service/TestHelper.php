<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Loader\AttributeClassLoader;

class TestHelper
{
    public static function phpAttributeSupportExists(): bool
    {
        return class_exists(AttributeRouteControllerLoader::class);
    }

    /**
     * Whether the installed symfony/routing still reads docblock annotations: its attribute loader lost the annotation
     * reader in 7.0. The bundle decides the same way (RoutingAttributeLoader), so a 6.4 framework next to routing 7 is
     * tested as the Symfony 7 case it is.
     */
    public static function docblockRoutingSupportExists(): bool
    {
        return !class_exists(AttributeClassLoader::class) || property_exists(AttributeClassLoader::class, 'reader');
    }
}
