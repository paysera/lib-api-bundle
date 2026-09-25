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

    public static function docblockRoutingSupportExists(): bool
    {
        return !class_exists(AttributeClassLoader::class) || property_exists(AttributeClassLoader::class, 'reader');
    }
}
