<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class MiscasedAliasImportController
{
    /**
     * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/miscased-alias-import', methods: ['GET'])]
    public function show()
    {
    }
}
