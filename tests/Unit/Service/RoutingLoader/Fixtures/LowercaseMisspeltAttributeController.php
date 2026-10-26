<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use paysera\bundle\apibundle\attributes\RequiredPermissions;

class LowercaseMisspeltAttributeController
{
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function show(): void
    {
    }
}
