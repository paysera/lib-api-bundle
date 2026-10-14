<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class UnimportedSplitAliasController
{
    /**
     * @Rest\
     *     RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/unimported-split-alias', methods: ['GET'])]
    public function show()
    {
    }
}
