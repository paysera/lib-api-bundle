<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Validator\Validation;

#[Validation]
class OtherLibraryClassAttributeController
{
    public function show(): void
    {
    }
}
