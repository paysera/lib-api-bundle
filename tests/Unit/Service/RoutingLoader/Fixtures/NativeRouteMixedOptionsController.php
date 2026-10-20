<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Attribute\Body;
use Symfony\Component\Routing\Annotation\Route;

class NativeRouteMixedOptionsController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Body(parameterName: 'data', denormalizationType: 'plain')]
    #[Route('/native-route-mixed-options', methods: ['POST'])]
    public function update($data): void
    {
    }
}
