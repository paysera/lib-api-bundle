<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class UnimportedColonBeforeBackslashController
{
    /**
     * @Rest:\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/unimported-colon-before-backslash', methods: ['GET'])]
    public function show()
    {
    }
}
