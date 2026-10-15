<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Some\Other\Library as Rest;
use Symfony\Component\Routing\Attribute\Route;

class SpacedForeignAliasController
{
    /**
     * @Rest \RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/spaced-foreign-alias', methods: ['GET'])]
    public function show()
    {
    }
}
