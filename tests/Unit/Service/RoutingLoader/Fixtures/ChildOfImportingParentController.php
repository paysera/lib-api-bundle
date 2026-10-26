<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @note migrated to attributes
 */
class ChildOfImportingParentController extends ImportingParentController
{
    /**
     * @note kept for older clients
     */
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[Route('/child-of-importing-parent', methods: ['GET'])]
    public function show(): void
    {
    }
}
