<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppPermission as Audit;
use Symfony\Component\Routing\Attribute\Route;

class TagNamedLikeImportsController
{
    /**
     * @Route kept as an attribute below
     * @Auditing pending
     * @note kept for older clients
     */
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[Route('/tag-named-like-imports', methods: ['GET'])]
    public function show(): void
    {
    }
}
