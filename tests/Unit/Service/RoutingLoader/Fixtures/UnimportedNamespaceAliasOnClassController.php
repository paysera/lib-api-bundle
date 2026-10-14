<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

/**
 * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
 */
class UnimportedNamespaceAliasOnClassController
{
    #[Route('/unimported-namespace-alias-on-class', methods: ['GET'])]
    public function show()
    {
    }
}
