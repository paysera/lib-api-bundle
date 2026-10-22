<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use App\Controller\RequiredPermissions;

#[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
class MissingClassAttributeClassController
{
    public function show(): void
    {
    }
}
