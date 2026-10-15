<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class UnimportedBareColonController
{
    /**
     * @RequiredPermissions:admin(permissions={"ROLE_ADMIN"})
     */
    #[Route('/unimported-bare-colon', methods: ['GET'])]
    public function show()
    {
    }
}
