<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CollidingNamespace\CollidingTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DeclaringNamespace\DeclaringTrait;

class TraitsInTwoNamespacesController
{
    use DeclaringTrait;
    use CollidingTrait;
}
