<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

class AliasedFullyQualifiedTraitMethodController
{
    use FullyQualifiedOptionsTrait, PlainShowTrait {
        PlainShowTrait::show insteadof FullyQualifiedOptionsTrait;
        FullyQualifiedOptionsTrait::show as index;
    }
}
