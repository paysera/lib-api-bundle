<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class OwnActionNextToAnnotatedTraitController
{
    use DocblockOptionsTrait;

    /**
     * @note kept for older clients
     */
    #[RequiredPermissions(permissions: ['ROLE_USER'])]
    #[Route('/own-action', methods: ['GET'])]
    public function index(): void
    {
    }
}
