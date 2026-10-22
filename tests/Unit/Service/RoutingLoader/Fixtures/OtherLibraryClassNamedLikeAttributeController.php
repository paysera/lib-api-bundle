<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Validator\Validation;

class OtherLibraryClassNamedLikeAttributeController
{
    #[Validation]
    public function show(): void
    {
    }
}
