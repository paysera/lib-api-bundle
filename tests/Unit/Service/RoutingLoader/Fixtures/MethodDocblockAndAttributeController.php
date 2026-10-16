<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Validation;
use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class MethodDocblockAndAttributeController
{
    /**
     * @Validation(enabled=false)
     */
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[Route('/method-docblock-and-attribute', methods: ['GET'])]
    public function show()
    {
    }
}
