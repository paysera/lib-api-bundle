<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class MissingParentInUnrelatedDocblockController
{
    /**
     * @note kept for older clients
     */
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[Route('/missing-parent-in-unrelated-docblock', methods: ['GET'])]
    public function show(): void
    {
    }

    /**
     * @ClassWithMissingParent
     */
    public function audit(): void
    {
    }
}
