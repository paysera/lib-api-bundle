<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle as Api;
use Symfony\Component\Routing\Attribute\Route;

class SpaceAfterParentAliasPathController
{
    /**
     * @Api\Annotation RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/space-after-parent-alias-path', methods: ['GET'])]
    public function show()
    {
    }
}
