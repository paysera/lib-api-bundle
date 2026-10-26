<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\{Annotation\RequiredPermissions};
use Symfony\Component\Routing\Attribute\Route;

class GroupUseDocblockController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/group-use', methods: ['GET'])]
    public function show(): void
    {
    }
}
