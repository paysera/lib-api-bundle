<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class BracedAliasAfterAnnotationController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     *
     * The {@Rest\RequiredPermissions} tag above guards the route.
     */
    #[Route('/braced-alias-after-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
