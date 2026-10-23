<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class InvalidUtf8DocblockController
{
    /**
     * Résumé of the exports
     * @\Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/invalid-utf8', methods: ['GET'])]
    public function show(): void
    {
    }
}
