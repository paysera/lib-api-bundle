<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;

class TestHelper
{
    public static function phpAttributeSupportExists(): bool
    {
        return PHP_VERSION_ID >= 80100 && class_exists(AttributeRouteControllerLoader::class);
    }
}
