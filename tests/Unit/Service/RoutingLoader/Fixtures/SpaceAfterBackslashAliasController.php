<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class SpaceAfterBackslashAliasController
{
    /**
     * @Rest\ RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/space-after-backslash-alias', methods: ['GET'])]
    public function show()
    {
    }
}
