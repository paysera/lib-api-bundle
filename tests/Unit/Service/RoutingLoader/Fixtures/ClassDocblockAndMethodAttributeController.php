<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Attribute\Body;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @RequiredPermissions(permissions={"ROLE_ADMIN"})
 */
class ClassDocblockAndMethodAttributeController
{
    #[Body(parameterName: 'data', denormalizationType: 'plain')]
    #[Route('/class-docblock-and-method-attribute', methods: ['POST'])]
    public function update($data): void
    {
    }
}
