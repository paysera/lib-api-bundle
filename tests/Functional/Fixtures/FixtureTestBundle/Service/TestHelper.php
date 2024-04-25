<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Service;

class TestHelper
{
    public static function phpAttributeSupportExists(): bool
    {
        return PHP_VERSION_ID >= 80100;
    }
}
