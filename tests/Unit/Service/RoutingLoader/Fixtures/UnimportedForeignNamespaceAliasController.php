<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class UnimportedForeignNamespaceAliasController
{
    /**
     * @Lib\Cache(maxAge=60)
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/unimported-foreign-namespace-alias', methods: ['GET'])]
    public function show()
    {
    }
}
