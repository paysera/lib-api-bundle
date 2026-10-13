<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibrary\Query as RequiredPermissions;

class TraitAliasCollidingWithClassAliasController
{
    use DocblockOptionsTrait;
}
