<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class QuoteBeforeAliasController
{
    /**
     * Exports the fields of `@Rest\Body` wrapped in " characters.
     *
     * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/quote-before-alias', methods: ['GET'])]
    public function show()
    {
    }
}
