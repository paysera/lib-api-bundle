<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Annotation\Route;

class NativeRouteDocblockOptionsController
{
    /**
     * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/native-route-docblock-options', methods: ['GET'])]
    public function show(): void
    {
    }
}
