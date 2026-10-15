<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use \Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class LeadingBackslashSpaceNotBackslashController
{
    /**
     * @Rest RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/leading-backslash-space-not-backslash', methods: ['GET'])]
    public function show()
    {
    }
}
