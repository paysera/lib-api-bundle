<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class SeeParenthesisAliasController
{
    /**
     * @see(@Rest\RequiredPermissions)
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/see-parenthesis-alias', methods: ['GET'])]
    public function show()
    {
    }
}
