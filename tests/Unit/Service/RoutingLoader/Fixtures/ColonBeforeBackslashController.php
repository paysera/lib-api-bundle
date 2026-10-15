<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class ColonBeforeBackslashController
{
    /**
     * @RequiredPermissions:\Admin(permissions={"ROLE_ADMIN"})
     */
    #[Route('/colon-before-backslash', methods: ['GET'])]
    public function show()
    {
    }
}
