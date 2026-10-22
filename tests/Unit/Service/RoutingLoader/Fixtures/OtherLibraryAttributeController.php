<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Acme\Missing\Get;
use Symfony\Component\Validator\Validation;

#[Validation]
class OtherLibraryAttributeController
{
    #[Validation]
    public function showClassNamedLikeAttribute(): void
    {
    }

    #[Get]
    public function showMissingClassWithOtherName(): void
    {
    }
}
