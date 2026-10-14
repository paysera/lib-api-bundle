<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class UnimportedSpacedAliasController
{
    /**
     * @Rest\ RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/unimported-spaced-alias', methods: ['GET'])]
    public function show()
    {
    }
}
