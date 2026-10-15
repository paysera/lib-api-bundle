<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class TodoParenthesisAliasController
{
    /**
     * @todo(@Rest\Query once v2 ships)
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/todo-parenthesis-alias', methods: ['GET'])]
    public function show()
    {
    }
}
