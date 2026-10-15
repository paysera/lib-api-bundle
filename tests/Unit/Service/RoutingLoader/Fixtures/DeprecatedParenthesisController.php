<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class DeprecatedParenthesisController
{
    /**
     * @deprecated (@Api\Query in v1) filter on the new endpoint instead
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/deprecated-parenthesis', methods: ['GET'])]
    public function show()
    {
    }
}
