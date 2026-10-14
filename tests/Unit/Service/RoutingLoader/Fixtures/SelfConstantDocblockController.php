<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class SelfConstantDocblockController
{
    public const ROLE = 'ROLE_ADMIN';

    /**
     * @RequiredPermissions(permissions={self::ROLE})
     */
    #[Route('/self-constant', methods: ['GET'])]
    public function show()
    {
    }
}
