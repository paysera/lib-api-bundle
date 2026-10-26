<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\Validation;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @RequiredPermissions(permissions={"ROLE_USER"})
 */
class ClassAndActionDocblockController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * @Validation(enabled=false)
     */
    #[Route('/class-and-action', methods: ['GET'])]
    public function show(): void
    {
    }
}
