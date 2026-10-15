<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class SpaceBeforeBackslashController
{
    /**
     * @Rest \RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/space-before-backslash', methods: ['GET'])]
    public function show()
    {
    }
}
