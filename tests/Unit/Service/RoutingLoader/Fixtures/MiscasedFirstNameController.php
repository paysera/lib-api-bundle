<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class MiscasedFirstNameController
{
    /**
     * @paysera\Bundle\ApiBundle\Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/miscased-first-name', methods: ['GET'])]
    public function show()
    {
    }
}
