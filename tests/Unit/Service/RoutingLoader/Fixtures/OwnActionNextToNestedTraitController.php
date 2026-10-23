<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class OwnActionNextToNestedTraitController
{
    use NestingTrait;

    #[RequiredPermissions(permissions: ['ROLE_USER'])]
    #[Route('/own-action-next-to-nested-trait', methods: ['GET'])]
    public function index(): void
    {
    }
}
