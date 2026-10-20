<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class FullyQualifiedDocblockController
{
    /**
     * @\Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/fully-qualified', methods: ['GET'])]
    public function show(): void
    {
    }
}
