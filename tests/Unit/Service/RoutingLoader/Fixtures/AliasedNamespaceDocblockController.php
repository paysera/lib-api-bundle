<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle as Api;
use Symfony\Component\Routing\Attribute\Route;

class AliasedNamespaceDocblockController
{
    /**
     * @Api\Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/aliased-namespace', methods: ['GET'])]
    public function show(): void
    {
    }
}
