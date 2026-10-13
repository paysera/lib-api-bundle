<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

/**
 * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
 */
class ClassAliasImportedByTraitController
{
    use AliasImportingTrait;

    #[Route('/class-alias-imported-by-trait', methods: ['GET'])]
    public function show()
    {
    }
}
