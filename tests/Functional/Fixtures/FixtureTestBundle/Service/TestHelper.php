<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\HttpKernel\Kernel;

class TestHelper
{
    public static function phpAttributeSupportExists(): bool
    {
        return class_exists(AttributeRouteControllerLoader::class);
    }

    /**
     * Symfony 7 removed the Doctrine annotation reader from routing: @Route docblocks are no longer read.
     */
    public static function docblockRoutingSupportExists(): bool
    {
        return Kernel::MAJOR_VERSION < 7;
    }
}
