<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

abstract class ParentWithoutImportController
{
    /**
     * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/parent-without-import', methods: ['GET'])]
    public function show(): void
    {
    }
}
