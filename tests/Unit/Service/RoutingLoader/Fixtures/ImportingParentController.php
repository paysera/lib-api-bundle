<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

abstract class ImportingParentController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_USER"})
     */
    #[Route('/importing-parent', methods: ['GET'])]
    public function index(): void
    {
    }
}
